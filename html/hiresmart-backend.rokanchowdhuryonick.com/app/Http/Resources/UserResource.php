<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'is_verified' => !is_null($this->email_verified_at),
            'member_since' => $this->created_at?->format('Y-m-d'),
            'profile_completed' => $this->when(
                $this->isCandidate() && $this->relationLoaded('profile'),
                fn() => $this->profile ? $this->profile->isComplete() : false
            ),
            
            // Include profile data for candidates when loaded
            'profile' => $this->when(
                $this->isCandidate() && $this->relationLoaded('profile'),
                fn() => new UserProfileResource($this->profile)
            ),
            
            // Include company data for employers when loaded
            'companies' => $this->when(
                $this->isEmployer() && $this->relationLoaded('companies'),
                fn() => CompanyResource::collection($this->companies)
            ),
            
            // Include skills when loaded
            'skills' => $this->when(
                $this->relationLoaded('skills'),
                fn() => SkillResource::collection($this->skills)
            ),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'role_display' => ucfirst($this->role),
                'account_type' => $this->role === 'candidate' ? 'Job Seeker' : 'Employer',
            ],
        ];
    }
} 