<?php

namespace App\Services;

use App\Models\JobPosting;
use App\Models\User;
use App\Models\Company;
use App\Models\Skill;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobService
{
    /**
     * Get jobs with filtering, search, and pagination
     */
    public function getJobs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = 'jobs_' . md5(serialize($filters) . $perPage);
        
        return Cache::remember($cacheKey, 300, function () use ($filters, $perPage) { // 5 minutes cache
            $query = JobPosting::query()
                ->with(['company', 'skills', 'country', 'state', 'city', 'area'])
                ->active();

            // Search by title/description
            if (!empty($filters['search'])) {
                $query->search($filters['search']);
            }

            // Filter by location
            if (!empty($filters['country_id']) || !empty($filters['state_id']) || 
                !empty($filters['city_id']) || !empty($filters['area_id'])) {
                $query->location(
                    $filters['country_id'] ?? null,
                    $filters['state_id'] ?? null,
                    $filters['city_id'] ?? null,
                    $filters['area_id'] ?? null
                );
            }

            // Filter by salary range
            if (!empty($filters['min_salary']) || !empty($filters['max_salary'])) {
                $query->salaryRange($filters['min_salary'] ?? null, $filters['max_salary'] ?? null);
            }

            // Filter by employment type
            if (!empty($filters['employment_type'])) {
                $query->employmentType($filters['employment_type']);
            }

            // Filter by skills
            if (!empty($filters['skills'])) {
                $skillIds = is_array($filters['skills']) ? $filters['skills'] : explode(',', $filters['skills']);
                $query->whereHas('skills', function ($q) use ($skillIds) {
                    $q->whereIn('skill_id', $skillIds);
                });
            }

            // Filter by company
            if (!empty($filters['company_id'])) {
                $query->where('company_id', $filters['company_id']);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            
            if (in_array($sortBy, ['created_at', 'min_salary', 'max_salary', 'deadline'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            return $query->paginate($perPage);
        });
    }

    /**
     * Get job by ID with full details
     */
    public function getJobById(int $id, bool $withApplications = false): JobPosting
    {
        $with = ['company', 'employer', 'skills', 'country', 'state', 'city', 'area'];
        
        if ($withApplications) {
            $with[] = 'applications.candidate';
        }

        $job = JobPosting::with($with)->findOrFail($id);

        // Check if job is active
        if ($job->status !== 'active' && !$job->isExpired()) {
            throw new \Exception('Job posting is not available', 404);
        }

        return $job;
    }

    /**
     * Create new job posting
     */
    public function createJob(User $employer, array $data): JobPosting
    {
        if (!$employer->isEmployer()) {
            throw new \Exception('Only employers can create job postings', 403);
        }

        DB::beginTransaction();
        try {
            // Get or create company
            $company = $this->getOrCreateCompany($employer, $data['company'] ?? []);

            // Create job posting
            $job = JobPosting::create([
                'user_id' => $employer->id,
                'company_id' => $company->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'min_salary' => $data['min_salary'] ?? null,
                'max_salary' => $data['max_salary'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'employment_type' => $data['employment_type'],
                'status' => $data['status'] ?? 'active',
                'deadline' => $data['deadline'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'country_id' => $data['country_id'],
                'state_id' => $data['state_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'area_id' => $data['area_id'] ?? null,
            ]);

            // Attach skills if provided
            if (!empty($data['skills'])) {
                $this->attachSkills($job, $data['skills']);
            }

            DB::commit();

            // Clear jobs cache
            $this->clearJobsCache();

            return $job->fresh(['company', 'skills', 'country', 'state', 'city', 'area']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update job posting
     */
    public function updateJob(JobPosting $job, User $employer, array $data): JobPosting
    {
        // Check ownership
        if ($job->user_id !== $employer->id && !$employer->isAdmin()) {
            throw new \Exception('Unauthorized to update this job', 403);
        }

        DB::beginTransaction();
        try {
            // Update company if provided
            if (isset($data['company']) && !empty($data['company'])) {
                $company = $this->getOrCreateCompany($employer, $data['company']);
                $data['company_id'] = $company->id;
                unset($data['company']);
            }

            // Remove skills from data array as it's handled separately
            $skills = $data['skills'] ?? null;
            unset($data['skills']);

            // Update job posting
            $job->update(array_filter($data, function ($value) {
                return $value !== null;
            }));

            // Update skills
            if (isset($skills)) {
                $job->skills()->detach();
                if (!empty($skills)) {
                    $this->attachSkills($job, $skills);
                }
            }

            DB::commit();

            // Clear jobs cache
            $this->clearJobsCache();

            return $job->fresh(['company', 'skills', 'country', 'state', 'city', 'area']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete job posting
     */
    public function deleteJob(JobPosting $job, User $employer): void
    {
        // Check ownership
        if ($job->user_id !== $employer->id && !$employer->isAdmin()) {
            throw new \Exception('Unauthorized to delete this job', 403);
        }

        $job->delete();

        // Clear jobs cache
        $this->clearJobsCache();
    }

    /**
     * Get employer's job postings
     */
    public function getEmployerJobs(User $employer, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (!$employer->isEmployer()) {
            throw new \Exception('Only employers can access this resource', 403);
        }

        $query = JobPosting::where('user_id', $employer->id)
            ->with(['company', 'skills', 'applications']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Archive old jobs (for scheduled task)
     */
    public function archiveOldJobs(int $daysOld = 30): int
    {
        $archivedCount = JobPosting::where('status', 'active')
            ->where('created_at', '<', now()->subDays($daysOld))
            ->update([
                'status' => 'archived',
                'archived_at' => now(),
            ]);

        // Clear jobs cache
        $this->clearJobsCache();

        return $archivedCount;
    }

    /**
     * Get job statistics for application stats
     */
    public function getJobStats(): array
    {
        return Cache::remember('job_stats', 300, function () {
            return [
                'total_active' => JobPosting::active()->count(),
                'total_all_time' => JobPosting::count(),
                'posted_today' => JobPosting::whereDate('created_at', today())->count(),
                'posted_this_week' => JobPosting::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'posted_this_month' => JobPosting::whereMonth('created_at', now()->month)->count(),
                'by_employment_type' => JobPosting::active()
                    ->selectRaw('employment_type, COUNT(*) as count')
                    ->groupBy('employment_type')
                    ->pluck('count', 'employment_type'),
            ];
        });
    }

    /**
     * Get or create company for employer
     */
    private function getOrCreateCompany(User $employer, array $companyData): Company
    {
        // If employer already has a company, use it
        $existingCompany = $employer->companies()->first();
        
        if ($existingCompany && empty($companyData)) {
            return $existingCompany;
        }

        // Create new company if data provided
        if (!empty($companyData['name'])) {
            return $employer->companies()->create($companyData);
        }

        // Use existing company or create default one
        if ($existingCompany) {
            return $existingCompany;
        }

        // Create default company
        return $employer->companies()->create([
            'name' => $employer->name . "'s Company",
            'description' => 'Company created automatically',
        ]);
    }

    /**
     * Attach skills to job posting
     */
    private function attachSkills(JobPosting $job, array $skills): void
    {
        $skillData = [];
        
        foreach ($skills as $skill) {
            $skillData[$skill['id']] = [
                'is_required' => $skill['is_required'] ?? false,
            ];
        }

        $job->skills()->attach($skillData);
    }

    /**
     * Clear jobs cache
     */
    private function clearJobsCache(): void
    {
        Cache::tags(['jobs'])->flush();
    }
} 