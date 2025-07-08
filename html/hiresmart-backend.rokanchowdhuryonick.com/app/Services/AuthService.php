<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => true,
        ]);

        // Create profile for candidates
        if ($user->isCandidate()) {
            $user->profile()->create([]);
        }

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user->fresh(),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Authenticate user and return token
     */
    public function login(array $credentials): array
    {
        // Validation is handled by LoginRequest Form Request
        if (!$token = JWTAuth::attempt($credentials)) {
            throw new \Exception('Invalid credentials', 401);
        }

        $user = JWTAuth::user();

        if (!$user->isActive()) {
            throw new \Exception('Account is deactivated', 403);
        }

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Refresh the JWT token
     */
    public function refresh(): array
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
        } catch (JWTException $e) {
            throw new \Exception('Token refresh failed', 401);
        }

        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Logout user by invalidating token
     */
    public function logout(): void
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            throw new \Exception('Token invalidation failed', 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(): User
    {
        return JWTAuth::user();
    }
} 