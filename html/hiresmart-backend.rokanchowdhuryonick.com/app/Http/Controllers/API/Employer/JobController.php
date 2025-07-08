<?php

namespace App\Http\Controllers\API\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\CreateJobRequest;
use App\Http\Requests\Job\UpdateJobRequest;
use App\Models\JobPosting;
use App\Services\JobService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class JobController extends Controller
{
    public function __construct(
        private JobService $jobService
    ) {
        // 
    }

    /**
     * Get employer's job postings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = min($request->get('per_page', 15), 50);
            $jobs = $this->jobService->getEmployerJobs(JWTAuth::user(), $filters, $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $jobs->items(),
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
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
     * Show specific job posting
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $job = $this->jobService->getJobById($id, true); // Include applications

            // Check ownership
            if ($job->user_id !== JWTAuth::user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view this job posting',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $job,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Create new job posting
     * 
     * @param CreateJobRequest $request
     * @return JsonResponse
     */
    public function store(CreateJobRequest $request): JsonResponse
    {
        try {
            $job = $this->jobService->createJob(JWTAuth::user(), $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Job posting created successfully',
                'data' => $job,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Update job posting
     * 
     * @param UpdateJobRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateJobRequest $request, int $id): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($id);
            $updatedJob = $this->jobService->updateJob($job, JWTAuth::user(), $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Job posting updated successfully',
                'data' => $updatedJob,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Delete job posting
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($id);
            $this->jobService->deleteJob($job, JWTAuth::user());

            return response()->json([
                'status' => 'success',
                'message' => 'Job posting deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get job statistics for employer
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $user = JWTAuth::user();
            $jobStats = $user->job_stats;
            $generalStats = $this->jobService->getJobStats();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'employer_stats' => $jobStats,
                    'platform_stats' => $generalStats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive job posting
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function archive(int $id): JsonResponse
    {
        try {
            $job = JobPosting::findOrFail($id);

            // Check ownership
            if ($job->user_id !== JWTAuth::user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to archive this job posting',
                ], 403);
            }

            $job->archive();

            return response()->json([
                'status' => 'success',
                'message' => 'Job posting archived successfully',
                'data' => $job->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
} 