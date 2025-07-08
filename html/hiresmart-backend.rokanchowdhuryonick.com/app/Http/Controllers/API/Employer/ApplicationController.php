<?php

namespace App\Http\Controllers\API\Employer;

use App\Http\Controllers\Controller;
use App\Services\ApplicationService;
use App\Services\MatchingService;
use App\Models\JobPosting;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\Application\UpdateApplicationStatusRequest;
use App\Http\Requests\Application\BulkUpdateApplicationsRequest;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $applicationService,
        private MatchingService $matchingService
    ) {
        // 
    }

    /**
     * Get all applications for employer's jobs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'job_id' => $request->get('job_id'),
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
                'sort_by' => $request->get('sort_by', 'applied_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = min($request->get('per_page', 15), 50);
            $applications = $this->applicationService->getEmployerApplications(JWTAuth::user(), $filters, $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $applications->items(),
                'pagination' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
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
     * Get applications for a specific job
     * 
     * @param Request $request
     * @param int $jobId
     * @return JsonResponse
     */
    public function jobApplications(Request $request, int $jobId): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($jobId);

            $filters = [
                'status' => $request->get('status'),
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
                'sort_by' => $request->get('sort_by', 'applied_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = min($request->get('per_page', 15), 50);
            $applications = $this->applicationService->getJobApplications($job, JWTAuth::user(), $filters, $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $applications->items(),
                'pagination' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                ],
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
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
     * Show specific application
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $application = $this->applicationService->getApplicationById($id, JWTAuth::user());

            return response()->json([
                'status' => 'success',
                'data' => $application,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Update application status
     * 
     * @param UpdateApplicationStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(UpdateApplicationStatusRequest $request, int $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $validated = $request->validated();
            
            $updatedApplication = $this->applicationService->updateApplicationStatus(
                $application,
                JWTAuth::user(),
                $validated['status'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Application status updated successfully',
                'data' => $updatedApplication,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Bulk update application statuses
     * 
     * @param BulkUpdateApplicationsRequest $request
     * @return JsonResponse
     */
    public function bulkUpdateStatus(BulkUpdateApplicationsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $updatedCount = $this->applicationService->bulkUpdateApplications(
                $validated['application_ids'],
                JWTAuth::user(),
                $validated['status']
            );

            return response()->json([
                'status' => 'success',
                'message' => "Successfully updated {$updatedCount} applications",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get application statistics for employer
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->applicationService->getApplicationStats(JWTAuth::user());

            return response()->json([
                'status' => 'success',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get candidate matches for a job
     * 
     * @param Request $request
     * @param int $jobId
     * @return JsonResponse
     */
    public function jobMatches(Request $request, int $jobId): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($jobId);
            $minScore = $request->get('min_score', 70);

            $matches = $this->matchingService->getJobMatches($job, JWTAuth::user(), $minScore);

            return response()->json([
                'status' => 'success',
                'data' => $matches,
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
                ],
                'meta' => [
                    'min_score_filter' => $minScore,
                    'total_matches' => $matches->count(),
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
     * Find potential candidates for a job
     * 
     * @param int $jobId
     * @return JsonResponse
     */
    public function findCandidates(int $jobId): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($jobId);

            // Check ownership
            if ($job->user_id !== JWTAuth::user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view candidates for this job',
                ], 403);
            }

            $matches = $this->matchingService->findMatchingCandidates($job);

            return response()->json([
                'status' => 'success',
                'data' => $matches,
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
                ],
                'meta' => [
                    'total_candidates_found' => count($matches),
                    'search_timestamp' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
} 