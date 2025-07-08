<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use App\Http\Controllers\API\Employer\JobController as EmployerJobController;
use App\Http\Controllers\API\Employer\ApplicationController as EmployerApplicationController;
use App\Http\Controllers\API\Candidate\JobController as CandidateJobController;
use App\Http\Controllers\API\Candidate\ApplicationController as CandidateApplicationController;
use App\Http\Controllers\API\Admin\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| HireSmart API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('ratelimit:3,60');
    Route::post('/login', [AuthController::class, 'login']);//->middleware('ratelimit:5,15');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('ratelimit:5,60');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('ratelimit:3,15');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('ratelimit:3,15');
});

// Public Browse APIs (no authentication required)
Route::get('/jobs', [CandidateJobController::class, 'index'])->middleware('ratelimit:100,60');
Route::get('/jobs/{id}', [CandidateJobController::class, 'show'])->middleware('ratelimit:200,60');
Route::get('/jobs/{id}/similar', [CandidateJobController::class, 'similar'])->middleware('ratelimit:50,60');
Route::get('/jobs/stats', [CandidateJobController::class, 'stats'])->middleware('ratelimit:30,60');



// Protected Routes (Authentication Required)
Route::middleware('auth:api')->group(function () {
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // Profile Management Routes
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/stats', [AuthController::class, 'userStats']);
    });
    
    // User Profile Route
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'user' => $request->user()
        ]);
    });
    
    // Employer API Routes
    Route::middleware('role:employer')->prefix('employer')->group(function () {
        // Job Management
        Route::apiResource('jobs', EmployerJobController::class);
        Route::post('jobs/{id}/archive', [EmployerJobController::class, 'archive']);
        Route::get('jobs-stats', [EmployerJobController::class, 'stats']);
        
        // Application Management
        Route::get('applications', [EmployerApplicationController::class, 'index']);
        Route::get('applications/{id}', [EmployerApplicationController::class, 'show']);
        Route::put('applications/{id}/status', [EmployerApplicationController::class, 'updateStatus']);
        Route::put('applications/bulk-status', [EmployerApplicationController::class, 'bulkUpdateStatus']);
        Route::get('applications-stats', [EmployerApplicationController::class, 'stats']);
        
        // Job-specific applications and matching
        Route::get('jobs/{jobId}/applications', [EmployerApplicationController::class, 'jobApplications']);
        Route::get('jobs/{jobId}/matches', [EmployerApplicationController::class, 'jobMatches']);
        Route::get('jobs/{jobId}/find-candidates', [EmployerApplicationController::class, 'findCandidates']);
    });
    
    // Candidate API Routes
    Route::middleware('role:candidate')->prefix('candidate')->group(function () {
        // Job Discovery
        Route::get('jobs', [CandidateJobController::class, 'index']);
        Route::get('jobs/{id}', [CandidateJobController::class, 'show']);
        
        // Rate limit job application endpoint (10 applications per 60 minutes to prevent abuse)
        Route::post('jobs/{id}/apply', [CandidateJobController::class, 'apply'])->middleware('ratelimit:10,60');
        
        Route::get('jobs/{id}/similar', [CandidateJobController::class, 'similar']);
        Route::post('jobs/{id}/bookmark', [CandidateJobController::class, 'bookmark']);
        Route::get('job-recommendations', [CandidateJobController::class, 'recommendations']);
        Route::get('jobs-stats', [CandidateJobController::class, 'stats']);
        
        // Application Management
        Route::get('applications', [CandidateApplicationController::class, 'index']);
        Route::get('applications/{id}', [CandidateApplicationController::class, 'show']);
        Route::delete('applications/{id}', [CandidateApplicationController::class, 'withdraw']);
        Route::get('applications-stats', [CandidateApplicationController::class, 'stats']);
        Route::get('applications-timeline', [CandidateApplicationController::class, 'timeline']);
        Route::get('recommendations', [CandidateApplicationController::class, 'recommendations']);
    });
    
    // Admin API Routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Dashboard and Analytics
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('system-health', [AdminController::class, 'systemHealth']);
        
        // User Management
        Route::get('users', [AdminController::class, 'users']);
        Route::put('users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        
        // System Operations
        Route::post('run-job-matching', [AdminController::class, 'runJobMatching']);
        Route::post('archive-old-jobs', [AdminController::class, 'archiveOldJobs']);
    });
    
    // Multi-role route (employer or admin)
    Route::middleware('role:employer,admin')->group(function () {
        Route::get('/management/overview', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Management overview - accessible by employers and admins',
                'user_role' => Auth::user()->role
            ]);
        });
    });
}); 