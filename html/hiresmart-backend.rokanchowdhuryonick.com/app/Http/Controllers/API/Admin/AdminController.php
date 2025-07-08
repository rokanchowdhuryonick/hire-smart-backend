<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Services\JobService;
use App\Services\ApplicationService;
use App\Services\MatchingService;
use App\Models\User;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminController extends Controller
{
    public function __construct(
        private UserService $userService,
        private JobService $jobService,
        private ApplicationService $applicationService,
        private MatchingService $matchingService
    ) {
        // Constructor...
    }

    /**
     * Get platform dashboard metrics
     * 
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'users' => $this->getUserStats(),
                'jobs' => $this->jobService->getJobStats(),
                'applications' => $this->applicationService->getApplicationStats(),
                'platform' => $this->getPlatformStats(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all users with filtering and pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $filters = [
                'role' => $request->get('role'),
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
                'is_verified' => $request->has('is_verified') ? $request->boolean('is_verified') : null,
                'search' => $request->get('search'),
                'recent_days' => $request->get('recent_days'),
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = min($request->get('per_page', 15), 100);
            $users = $this->userService->getUsers(array_filter($filters), $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
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
     * Toggle user active status
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function toggleUserStatus(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent admin from deactivating themselves
            if ($user->id === JWTAuth::user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot deactivate your own account',
                ], 400);
            }

            $updatedUser = $this->userService->toggleUserStatus($user);

            return response()->json([
                'status' => 'success',
                'message' => $updatedUser->is_active ? 'User activated successfully' : 'User deactivated successfully',
                'data' => $updatedUser,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Run job matching manually
     * 
     * @return JsonResponse
     */
    public function runJobMatching(): JsonResponse
    {
        try {
            $result = $this->matchingService->runJobMatching();

            return response()->json([
                'status' => 'success',
                'message' => 'Job matching completed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive old jobs manually
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function archiveOldJobs(Request $request): JsonResponse
    {
        try {
            $daysOld = $request->get('days_old', 30);
            $archivedCount = $this->jobService->archiveOldJobs($daysOld);

            return response()->json([
                'status' => 'success',
                'message' => "Successfully archived {$archivedCount} old job postings",
                'archived_count' => $archivedCount,
                'days_old' => $daysOld,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system health and performance metrics
     * 
     * @return JsonResponse
     */
    public function systemHealth(): JsonResponse
    {
        try {
            $health = [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'storage' => $this->checkStorageHealth(),
                'queues' => $this->checkQueueHealth(),
            ];

            $overallStatus = collect($health)->every(fn($status) => $status['status'] === 'healthy') ? 'healthy' : 'degraded';

            return response()->json([
                'status' => 'success',
                'overall_status' => $overallStatus,
                'components' => $health,
                'checked_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    private function getUserStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::active()->count(),
            'verified' => User::verified()->count(),
            'by_role' => [
                'admins' => User::admins()->count(),
                'employers' => User::employers()->count(),
                'candidates' => User::candidates()->count(),
            ],
            'recent_registrations' => [
                'today' => User::recentRegistrations(1)->count(),
                'this_week' => User::recentRegistrations(7)->count(),
                'this_month' => User::recentRegistrations(30)->count(),
            ],
        ];
    }

    /**
     * Get platform-wide statistics
     */
    private function getPlatformStats(): array
    {
        return [
            'database_size' => $this->getDatabaseSize(),
            'cache_usage' => $this->getCacheUsage(),
            'top_skills' => $this->getTopSkills(),
            'top_companies' => $this->getTopCompanies(),
            'geographic_distribution' => $this->getGeographicDistribution(),
        ];
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'connection' => 'active',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth(): array
    {
        try {
            $start = microtime(true);
            cache()->put('health_check', 'ok', 5);
            $value = cache()->get('health_check');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => $value === 'ok' ? 'healthy' : 'unhealthy',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health
     */
    private function checkStorageHealth(): array
    {
        try {
            $path = storage_path();
            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);
            $usedPercentage = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);

            return [
                'status' => $usedPercentage < 90 ? 'healthy' : 'warning',
                'used_percentage' => $usedPercentage,
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health
     */
    private function checkQueueHealth(): array
    {
        try {
            // This is a simplified check - in production you'd check actual queue metrics
            return [
                'status' => 'healthy',
                'connection' => 'redis',
                'note' => 'Queue health monitoring requires additional implementation',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get database size
     */
    private function getDatabaseSize(): string
    {
        try {
            $size = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size")[0]->size ?? 'Unknown';
            return $size;
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get cache usage
     */
    private function getCacheUsage(): string
    {
        return 'Redis cache active'; // Simplified - would need Redis-specific commands for actual usage
    }

    /**
     * Get top skills
     */
    private function getTopSkills(): array
    {
        return DB::table('user_skills')
            ->join('skills', 'user_skills.skill_id', '=', 'skills.id')
            ->select('skills.name', DB::raw('COUNT(*) as count'))
            ->groupBy('skills.id', 'skills.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get top companies
     */
    private function getTopCompanies(): array
    {
        return DB::table('companies')
            ->select('name', DB::raw('COUNT(job_postings.id) as job_count'))
            ->leftJoin('job_postings', 'companies.id', '=', 'job_postings.company_id')
            ->groupBy('companies.id', 'companies.name')
            ->orderByDesc('job_count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get geographic distribution
     */
    private function getGeographicDistribution(): array
    {
        return DB::table('user_profiles')
            ->join('cities', 'user_profiles.city_id', '=', 'cities.id')
            ->join('states', 'cities.state_id', '=', 'states.id')
            ->join('countries', 'states.country_id', '=', 'countries.id')
            ->select('countries.name as country', 'states.name as state', DB::raw('COUNT(*) as count'))
            ->groupBy('countries.id', 'countries.name', 'states.id', 'states.name')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->toArray();
    }
} 