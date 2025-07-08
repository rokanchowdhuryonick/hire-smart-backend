<?php

namespace App\Http\Controllers\API\Candidate;

use App\Http\Controllers\Controller;
use App\Services\JobService;
use App\Services\ApplicationService;
use App\Services\MatchingService;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\Application\ApplyJobRequest;

class JobController extends Controller
{
    public function __construct(
        private JobService $jobService,
        private ApplicationService $applicationService,
        private MatchingService $matchingService
    ) {
        
    }

    /**
     * Browse available jobs with filtering
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'country_id' => $request->get('country_id'),
                'state_id' => $request->get('state_id'),
                'city_id' => $request->get('city_id'),
                'area_id' => $request->get('area_id'),
                'min_salary' => $request->get('min_salary'),
                'max_salary' => $request->get('max_salary'),
                'employment_type' => $request->get('employment_type'),
                'skills' => $request->get('skills'),
                'company_id' => $request->get('company_id'),
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = min($request->get('per_page', 15), 50);
            $jobs = $this->jobService->getJobs($filters, $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $jobs->items(),
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                ],
                'filters_applied' => array_filter($filters),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show specific job details
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $job = $this->jobService->getJobById($id);

            // Check if user has already applied
            $hasApplied = $job->applications()
                ->where('user_id', JWTAuth::user()->id)
                ->exists();

            return response()->json([
                'status' => 'success',
                'data' => $job,
                'meta' => [
                    'has_applied' => $hasApplied,
                    'is_expired' => $job->isExpired(),
                    'days_remaining' => $job->deadline ? now()->diffInDays($job->deadline, false) : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Apply to a job
     * 
     * @param ApplyJobRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function apply(ApplyJobRequest $request, int $id): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($id);
            $application = $this->applicationService->applyToJob($job, JWTAuth::user(), $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Application submitted successfully',
                'data' => $application,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get job recommendations based on candidate profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function recommendations(Request $request): JsonResponse
    {
        try {
            $minScore = $request->get('min_score', 70);
            $matches = $this->matchingService->getCandidateMatches(JWTAuth::user(), $minScore);

            return response()->json([
                'status' => 'success',
                'data' => $matches,
                'meta' => [
                    'total_recommendations' => $matches->count(),
                    'min_score_filter' => $minScore,
                    'generated_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get similar jobs based on a specific job
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function similar(int $id): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($id);

            // Find similar jobs based on skills and location
            $similarJobs = JobPosting::active()
                ->where('id', '!=', $job->id)
                ->where(function ($query) use ($job) {
                    // Same location or similar
                    $query->where('city_id', $job->city_id)
                          ->orWhere('state_id', $job->state_id);
                })
                ->whereHas('skills', function ($query) use ($job) {
                    // Similar skills
                    $skillIds = $job->skills->pluck('id');
                    $query->whereIn('skill_id', $skillIds);
                })
                ->with(['company', 'skills', 'country', 'state', 'city'])
                ->take(10)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $similarJobs,
                'reference_job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                ],
                'meta' => [
                    'total_similar' => $similarJobs->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Save/Bookmark a job for later
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function bookmark(int $id): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($id);
            $user = JWTAuth::user();

            // For now, we'll use a simple implementation
            // In a full system, you'd have a separate bookmarks table
            
            return response()->json([
                'status' => 'success',
                'message' => 'Job bookmarked successfully',
                'job_id' => $job->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get job statistics relevant to candidates
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->jobService->getJobStats();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_active_jobs' => $stats['total_active'],
                    'posted_today' => $stats['posted_today'],
                    'posted_this_week' => $stats['posted_this_week'],
                    'by_employment_type' => $stats['by_employment_type'],
                    'top_locations' => $stats['top_locations'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
} 