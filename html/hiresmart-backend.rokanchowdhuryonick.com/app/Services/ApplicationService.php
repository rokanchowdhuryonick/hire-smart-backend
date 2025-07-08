<?php

namespace App\Services;

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use App\Models\Notification;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApplicationService
{
    /**
     * Apply to job posting
     */
    public function applyToJob(JobPosting $job, User $candidate, array $data): Application
    {
        if (!$candidate->isCandidate()) {
            throw new \Exception('Only candidates can apply to jobs', 403);
        }

        if (!$job->isActive()) {
            throw new \Exception('This job posting is no longer active', 400);
        }

        if ($job->isExpired()) {
            throw new \Exception('Application deadline has passed', 400);
        }

        // Check if user has already applied
        $existingApplication = Application::where('job_posting_id', $job->id)
            ->where('user_id', $candidate->id)
            ->first();

        if ($existingApplication) {
            throw new \Exception('You have already applied to this job', 400);
        }

        DB::beginTransaction();
        try {
            $application = Application::create([
                'job_posting_id' => $job->id,
                'user_id' => $candidate->id,
                'cover_letter' => $data['cover_letter'] ?? null,
                'resume_path' => $data['resume_path'] ?? null,
                'status' => 'pending',
                'applied_at' => now(),
            ]);

            // Notify employer about new application
            Notification::create([
                'user_id' => $job->employer->id,
                'type' => 'application_status',
                'title' => 'New Job Application',
                'message' => "A new candidate has applied for '{$job->title}' position.",
            ]);

            DB::commit();

            // Clear application cache for this candidate and employer
            $this->clearApplicationCache($candidate->id);
            $this->clearApplicationCache($job->user_id);

            return $application->load(['jobPosting', 'candidate']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get candidate's applications
     */
    public function getCandidateApplications(User $candidate, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (!$candidate->isCandidate()) {
            throw new \Exception('Only candidates can access this resource', 403);
        }

        $query = Application::where('user_id', $candidate->id)
            ->with(['jobPosting.company', 'jobPosting.skills']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->where('applied_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('applied_at', '<=', $filters['to_date']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'applied_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get job applications for employer
     */
    public function getJobApplications(JobPosting $job, User $employer, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Check ownership
        if ($job->user_id !== $employer->id && !$employer->isAdmin()) {
            throw new \Exception('Unauthorized to view these applications', 403);
        }

        $query = Application::where('job_posting_id', $job->id)
            ->with(['candidate.profile', 'candidate.skills']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->where('applied_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('applied_at', '<=', $filters['to_date']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'applied_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get all applications for employer across all their jobs
     */
    public function getEmployerApplications(User $employer, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (!$employer->isEmployer()) {
            throw new \Exception('Only employers can access this resource', 403);
        }

        $query = Application::whereHas('jobPosting', function ($q) use ($employer) {
            $q->where('user_id', $employer->id);
        })->with(['jobPosting', 'candidate.profile']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        // Filter by job
        if (!empty($filters['job_id'])) {
            $query->forJob($filters['job_id']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->where('applied_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('applied_at', '<=', $filters['to_date']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'applied_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Update application status (Employer only)
     */
    public function updateApplicationStatus(Application $application, User $employer, string $status, string $notes = null): Application
    {
        // Check ownership
        if ($application->jobPosting->user_id !== $employer->id && !$employer->isAdmin()) {
            throw new \Exception('Unauthorized to update this application', 403);
        }

        // Validate status
        $validStatuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid application status', 400);
        }

        DB::beginTransaction();
        try {
            $application->update([
                'status' => $status,
                'reviewed_at' => $status !== 'pending' ? now() : null,
            ]);

            // Notify candidate about status change
            if ($status !== 'pending') {
                Notification::createApplicationStatus(
                    $application->user_id,
                    $application->jobPosting->title,
                    $status
                );
            }

            DB::commit();

            // Clear application cache
            $this->clearApplicationCache($application->user_id);
            $this->clearApplicationCache($employer->id);

            return $application->fresh(['jobPosting', 'candidate']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Bulk update application statuses
     */
    public function bulkUpdateApplications(array $applicationIds, User $employer, string $status): int
    {
        $applications = Application::whereIn('id', $applicationIds)
            ->whereHas('jobPosting', function ($q) use ($employer) {
                $q->where('user_id', $employer->id);
            })
            ->get();

        if ($applications->isEmpty()) {
            throw new \Exception('No applications found or unauthorized', 404);
        }

        $updatedCount = 0;
        
        DB::beginTransaction();
        try {
            foreach ($applications as $application) {
                $application->update([
                    'status' => $status,
                    'reviewed_at' => $status !== 'pending' ? now() : null,
                ]);

                // Notify candidate
                if ($status !== 'pending') {
                    Notification::createApplicationStatus(
                        $application->user_id,
                        $application->jobPosting->title,
                        $status
                    );
                }

                $updatedCount++;
            }

            DB::commit();

            // Clear cache
            $this->clearApplicationCache($employer->id);

            return $updatedCount;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get application by ID with ownership check
     */
    public function getApplicationById(int $id, User $user): Application
    {
        $application = Application::with(['jobPosting.company', 'candidate.profile'])
            ->findOrFail($id);

        // Check ownership (candidate owns the application OR employer owns the job)
        if ($user->isCandidate() && $application->user_id !== $user->id) {
            throw new \Exception('Unauthorized to view this application', 403);
        }

        if ($user->isEmployer() && $application->jobPosting->user_id !== $user->id) {
            throw new \Exception('Unauthorized to view this application', 403);
        }

        return $application;
    }

    /**
     * Withdraw application (Candidate only)
     */
    public function withdrawApplication(Application $application, User $candidate): void
    {
        if ($application->user_id !== $candidate->id) {
            throw new \Exception('Unauthorized to withdraw this application', 403);
        }

        if ($application->status === 'hired') {
            throw new \Exception('Cannot withdraw hired application', 400);
        }

        DB::beginTransaction();
        try {
            $application->delete();

            // Notify employer
            Notification::create([
                'user_id' => $application->jobPosting->user_id,
                'type' => 'application_status',
                'title' => 'Application Withdrawn',
                'message' => "A candidate has withdrawn their application for '{$application->jobPosting->title}'.",
            ]);

            DB::commit();

            // Clear cache
            $this->clearApplicationCache($candidate->id);
            $this->clearApplicationCache($application->jobPosting->user_id);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get application statistics
     */
    public function getApplicationStats(User $user = null): array
    {
        $cacheKey = 'application_stats' . ($user ? '_' . $user->id : '');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            if ($user && $user->isCandidate()) {
                return $this->getCandidateStats($user);
            }

            if ($user && $user->isEmployer()) {
                return $this->getEmployerStats($user);
            }

            // Global stats for admin
            return [
                'total_applications' => Application::count(),
                'pending_applications' => Application::pending()->count(),
                'reviewed_applications' => Application::reviewed()->count(),
                'applications_today' => Application::whereDate('applied_at', today())->count(),
                'applications_this_week' => Application::whereBetween('applied_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'applications_this_month' => Application::whereMonth('applied_at', now()->month)->count(),
                'by_status' => Application::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
            ];
        });
    }

    /**
     * Get candidate application statistics
     */
    private function getCandidateStats(User $candidate): array
    {
        $applications = $candidate->applications();

        return [
            'total' => $applications->count(),
            'pending' => $applications->pending()->count(),
            'reviewed' => $applications->status('reviewed')->count(),
            'shortlisted' => $applications->status('shortlisted')->count(),
            'rejected' => $applications->status('rejected')->count(),
            'hired' => $applications->status('hired')->count(),
            'recent' => $applications->recent()->count(),
            'success_rate' => $this->calculateSuccessRate($applications),
        ];
    }

    /**
     * Get employer application statistics
     */
    private function getEmployerStats(User $employer): array
    {
        $applications = Application::whereHas('jobPosting', function ($q) use ($employer) {
            $q->where('user_id', $employer->id);
        });

        return [
            'total_received' => $applications->count(),
            'pending_review' => $applications->pending()->count(),
            'reviewed' => $applications->reviewed()->count(),
            'recent' => $applications->recent()->count(),
            'by_job' => Application::select('job_posting_id')
                ->whereHas('jobPosting', function ($q) use ($employer) {
                    $q->where('user_id', $employer->id);
                })
                ->with('jobPosting:id,title')
                ->selectRaw('job_posting_id, COUNT(*) as count')
                ->groupBy('job_posting_id')
                ->get(),
        ];
    }

    /**
     * Calculate candidate success rate
     */
    private function calculateSuccessRate($applications): float
    {
        $total = $applications->count();
        if ($total === 0) return 0;

        $successful = $applications->whereIn('status', ['shortlisted', 'hired'])->count();
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Clear application cache
     */
    private function clearApplicationCache(int $userId): void
    {
        Cache::forget('application_stats_' . $userId);
        Cache::tags(['applications'])->flush();
    }
} 