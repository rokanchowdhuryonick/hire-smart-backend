<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\AuthResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\StatsResource;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private UserService $userService
    ) {
        // 
    }

    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return (new AuthResource(
                $result['user'], 
                $result['token'], 
                $result['token_type'], 
                $result['expires_in']
            ))->additional([
                'status' => 'success',
                'message' => 'Registration successful'
            ])->response()->setStatusCode(201);
        } catch (\Exception $e) {
            return ErrorResource::serverError($e->getMessage())
                ->response()
                ->setStatusCode($e->getCode() ?: 500);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return (new AuthResource(
                $result['user'], 
                $result['token'], 
                $result['token_type'], 
                $result['expires_in']
            ))->additional([
                'status' => 'success',
                'message' => 'Login successful'
            ])->response();
        } catch (\Exception $e) {
            return ErrorResource::unauthorized($e->getMessage())
                ->response()
                ->setStatusCode($e->getCode() ?: 401);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = $this->authService->me();

            return (new UserResource($user))
                ->additional([
                    'status' => 'success'
                ])
                ->response();
        } catch (\Exception $e) {
            return ErrorResource::unauthorized($e->getMessage())
                ->response()
                ->setStatusCode(401);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return SuccessResource::noContent('Successfully logged out')
                ->response();
        } catch (\Exception $e) {
            return ErrorResource::serverError($e->getMessage())
                ->response()
                ->setStatusCode(500);
        }
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();

            return response()->json([
                'status' => 'success',
                'authorization' => [
                    'token' => $result['token'],
                    'type' => $result['token_type'],
                    'expires_in' => $result['expires_in']
                ]
            ]);
        } catch (\Exception $e) {
            return ErrorResource::unauthorized($e->getMessage())
                ->response()
                ->setStatusCode(401);
        }
    }

    /**
     * Update user profile
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = JWTAuth::user();
            $updatedUser = $this->userService->updateProfile($user, $request->validated());

            return SuccessResource::updated(
                new UserResource($updatedUser),
                'Profile updated successfully'
            )->response();
        } catch (\Exception $e) {
            return ErrorResource::serverError($e->getMessage())
                ->response()
                ->setStatusCode($e->getCode() ?: 500);
        }
    }

    /**
     * Change user password
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = JWTAuth::user();
            $this->userService->changePassword($user, $request->validated());

            return SuccessResource::noContent('Password changed successfully')
                ->response();
        } catch (\Exception $e) {
            return ErrorResource::serverError($e->getMessage())
                ->response()
                ->setStatusCode($e->getCode() ?: 400);
        }
    }

    /**
     * Get user statistics
     *
     * @return JsonResponse
     */
    public function userStats(): JsonResponse
    {
        try {
            $user = JWTAuth::user();
            $stats = $this->userService->getUserStats($user);

            return (new StatsResource($stats, 'user'))
                ->additional([
                    'status' => 'success'
                ])
                ->response();
        } catch (\Exception $e) {
            return ErrorResource::serverError($e->getMessage())
                ->response()
                ->setStatusCode(500);
        }
    }
}
