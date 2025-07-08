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
            'is_active' => false, // User must verify email first
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
            'message' => 'Registration successful. Please verify your email to activate your account.',
        ];
    }

    /**
     * Authenticate user and return token
     */
    public function login(array $credentials): array
    {
        // First, find the user and verify credentials manually
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Invalid credentials', 401);
        }

        // Check if email is verified BEFORE creating token
        if (!$user->isVerified()) {
            throw new \Exception('Please verify your email address before logging in', 403);
        }

        // Check if account is active BEFORE creating token
        if (!$user->isActive()) {
            throw new \Exception('Account is deactivated', 403);
        }

        // Only create token if user is verified and active
        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Verify user email
     */
    public function verifyEmail(string $email): array
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            throw new \Exception('User with this email address not found', 404);
        }
        
        if ($user->isVerified()) {
            throw new \Exception('Email is already verified', 400);
        }

        // Verify the user and activate the account
        $user->update([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user->fresh(),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'message' => 'Email verified successfully. Account is now active.',
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