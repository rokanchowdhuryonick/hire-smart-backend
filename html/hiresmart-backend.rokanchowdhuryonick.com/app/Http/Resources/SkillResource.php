<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillResource extends JsonResource
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
            'category' => $this->category,
            'description' => $this->description,
            
            // When this skill is attached to a job/user with pivot data
            'pivot' => $this->when(
                $this->pivot,
                fn() => [
                    'is_required' => $this->pivot->is_required ?? false,
                    'experience_level' => $this->pivot->experience_level ?? null,
                    'years_experience' => $this->pivot->years_experience ?? null,
                ]
            ),
            
            // Skill statistics when loaded
            'stats' => $this->when(
                isset($this->jobs_count) || isset($this->users_count),
                fn() => [
                    'jobs_count' => $this->jobs_count ?? null,
                    'users_count' => $this->users_count ?? null,
                    'demand_level' => $this->getDemandLevel(),
                ]
            ),
        ];
    }

    /**
     * Get skill demand level based on usage
     */
    private function getDemandLevel(): string
    {
        $jobsCount = $this->jobs_count ?? 0;
        
        if ($jobsCount >= 100) return 'high';
        if ($jobsCount >= 50) return 'medium';
        if ($jobsCount >= 10) return 'low';
        
        return 'very_low';
    }
} 