<?php

namespace App\Http\Controllers\API\Candidate;

use App\Http\Controllers\Controller;
use App\Services\ApplicationService;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $applicationService
    ) {
        
    }

    /**
     * Get candidate's applications
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
                'sort_by' => $request->get('sort_by', 'applied_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = min($request->get('per_page', 15), 50);
            $applications = $this->applicationService->getCandidateApplications(JWTAuth::user(), $filters, $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $applications->items(),
                'pagination' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                ],
                'filters_applied' => array_filter($filters),
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
                'meta' => [
                    'can_withdraw' => $application->status !== 'hired',
                    'days_since_applied' => $application->days_since_application,
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
     * Withdraw application
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function withdraw(int $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $this->applicationService->withdrawApplication($application, JWTAuth::user());

            return response()->json([
                'status' => 'success',
                'message' => 'Application withdrawn successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get application statistics for candidate
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
     * Get application timeline/history
     * 
     * @return JsonResponse
     */
    public function timeline(): JsonResponse
    {
        try {
            $applications = JWTAuth::user()
                ->applications()
                ->with(['jobPosting:id,title', 'jobPosting.company:id,name'])
                ->select(['id', 'job_posting_id', 'status', 'applied_at', 'reviewed_at'])
                ->orderBy('applied_at', 'desc')
                ->take(20)
                ->get()
                ->map(function ($application) {
                    return [
                        'id' => $application->id,
                        'job_title' => $application->jobPosting->title,
                        'company_name' => $application->jobPosting->company->name ?? 'Unknown',
                        'status' => $application->status,
                        'status_color' => $application->status_color,
                        'applied_at' => $application->applied_at,
                        'reviewed_at' => $application->reviewed_at,
                        'days_ago' => $application->applied_at->diffInDays(now()),
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $applications,
                'meta' => [
                    'total_shown' => $applications->count(),
                    'note' => 'Showing latest 20 applications',
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
     * Get recommended actions for candidate
     * 
     * @return JsonResponse
     */
    public function recommendations(): JsonResponse
    {
        try {
            $user = JWTAuth::user();
            $recommendations = [];

            // Check profile completion
            $profile = $user->profile;
            if (!$profile || empty($profile->bio) || empty($profile->phone)) {
                $recommendations[] = [
                    'type' => 'profile_completion',
                    'priority' => 'high',
                    'message' => 'Complete your profile to get better job matches',
                    'action' => 'Update your bio and contact information',
                ];
            }

            // Check skills
            if ($user->skills()->count() < 3) {
                $recommendations[] = [
                    'type' => 'skills',
                    'priority' => 'medium',
                    'message' => 'Add more skills to your profile',
                    'action' => 'Add at least 3 skills to improve job matching',
                ];
            }

            // Check recent activity
            $recentApplications = $user->applications()
                ->where('applied_at', '>=', now()->subWeeks(2))
                ->count();

            if ($recentApplications === 0) {
                $recommendations[] = [
                    'type' => 'activity',
                    'priority' => 'medium',
                    'message' => 'Stay active in your job search',
                    'action' => 'Browse and apply to relevant job postings',
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $recommendations,
                'meta' => [
                    'total_recommendations' => count($recommendations),
                    'generated_at' => now()->toISOString(),
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