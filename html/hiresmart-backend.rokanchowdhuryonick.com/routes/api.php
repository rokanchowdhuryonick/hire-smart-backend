<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Public Routes (No Authentication Required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Password Reset Routes
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

// Protected Routes (Authentication Required)
Route::middleware('auth:api')->group(function () {
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });
    
    // User Profile Route
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'user' => $request->user()
        ]);
    });
    
    // Role-based test routes for middleware testing
    
    // Employer-only routes
    Route::middleware('role:employer')->group(function () {
        Route::get('/employer/dashboard', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Welcome to Employer Dashboard!',
                'user' => auth()->user()
            ]);
        });
        
        Route::get('/employer/jobs', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Employer job listings',
                'user_role' => auth()->user()->role
            ]);
        });
    });
    
    // Candidate-only routes
    Route::middleware('role:candidate')->group(function () {
        Route::get('/candidate/dashboard', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Welcome to Candidate Dashboard!',
                'user' => auth()->user()
            ]);
        });
        
        Route::get('/candidate/applications', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Your job applications',
                'user_role' => auth()->user()->role
            ]);
        });
    });
    
    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Welcome to Admin Dashboard!',
                'user' => auth()->user()
            ]);
        });
        
        Route::get('/admin/users', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'All system users',
                'user_role' => auth()->user()->role
            ]);
        });
    });
    
    // Multi-role route (employer or admin)
    Route::middleware('role:employer,admin')->group(function () {
        Route::get('/management/overview', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Management overview - accessible by employers and admins',
                'user_role' => auth()->user()->role
            ]);
        });
    });
}); 