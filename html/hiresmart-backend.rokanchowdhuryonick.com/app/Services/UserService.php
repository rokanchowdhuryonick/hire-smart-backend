<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        // Basic user data
        $userFields = ['name', 'email'];
        $userData = array_intersect_key($data, array_flip($userFields));

        if (!empty($userData)) {
            $user->update($userData);
        }

        // Profile data for candidates
        if ($user->isCandidate() && isset($data['profile'])) {
            $profileData = $data['profile'];
            $profile = $user->getOrCreateProfile();
            $profile->update($profileData);
        }

        return $user->fresh(['profile']);
    }

    /**
     * Change user password
     */
    public function changePassword(User $user, array $data): void
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            throw new \Exception('Current password is incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);
    }

    /**
     * Activate/Deactivate user account (Admin function)
     */
    public function toggleUserStatus(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);
        return $user->fresh();
    }

    /**
     * Get user statistics
     */
    public function getUserStats(User $user): array
    {
        $stats = [
            'profile_completed' => false,
            'last_login' => $user->updated_at,
        ];

        if ($user->isCandidate()) {
            $stats = array_merge($stats, [
                'application_stats' => $user->application_stats,
                'profile_completed' => $this->isProfileComplete($user),
                'skills_count' => $user->skills()->count(),
                'recent_matches' => $user->jobMatches()->recent(7)->count(),  // ← Fix N+1: Use count query
            ]);
        }

        if ($user->isEmployer()) {
            $stats = array_merge($stats, [
                'job_stats' => $user->job_stats,
                'companies_count' => $user->companies()->count(),
            ]);
        }

        return $stats;
    }

    /**
     * Check if candidate profile is complete
     */
    public function isProfileComplete(User $user): bool
    {
        if (!$user->isCandidate() || !$user->profile) {
            return false;
        }

        $profile = $user->profile;
        
        return !empty($profile->bio) 
            && !empty($profile->phone)
            && $profile->min_salary 
            && $profile->max_salary
            && $profile->country_id
            && $profile->state_id
            && $profile->city_id
            && $profile->area_id
            && $user->skills()->count() > 0;
    }

    /**
     * Get user by ID with relationships
     */
    public function getUserById(int $id, array $relationships = []): User
    {
        return User::with($relationships)->findOrFail($id);
    }

    /**
     * Get users with filtering and pagination
     */
    public function getUsers(array $filters = [], int $perPage = 15)
    {
        $query = User::with(['profile']);

        // Filter by role
        if (isset($filters['role'])) {
            $query->role($filters['role']);
        }

        // Filter by status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter by verification status
        if (isset($filters['is_verified'])) {
            if ($filters['is_verified']) {
                $query->verified();
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Search by name or email
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        // Recent registrations
        if (isset($filters['recent_days'])) {
            $query->recentRegistrations($filters['recent_days']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Verify user email
     */
    public function verifyUser(User $user): User
    {
        $user->update([
            'email_verified_at' => now(),
        ]);

        return $user->fresh();
    }
} 