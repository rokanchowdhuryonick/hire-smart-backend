<?php

use App\Http\Controllers\API\Auth\AuthController;
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
    
    // TODO: Add more protected routes here
    // Route::prefix('jobs')->group(function () {
    //     Route::get('/', [JobController::class, 'index']);
    //     Route::get('/{job}', [JobController::class, 'show']);
    // });
    
    // TODO: Employer-only routes will go here
    // Route::middleware('role:employer')->group(function () {
    //     Route::post('/jobs', [JobController::class, 'store']);
    //     Route::put('/jobs/{job}', [JobController::class, 'update']);
    // });
    
    // TODO: Admin-only routes will go here
    // Route::middleware('role:admin')->group(function () {
    //     Route::get('/admin/users', [AdminController::class, 'users']);
    //     Route::get('/admin/metrics', [AdminController::class, 'metrics']);
    // });
}); 