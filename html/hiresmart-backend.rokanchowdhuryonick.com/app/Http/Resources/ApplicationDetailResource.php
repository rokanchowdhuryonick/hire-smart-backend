<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationDetailResource extends JsonResource
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
            'status' => $this->status,
            'status_display' => $this->getStatusDisplay(),
            'status_color' => $this->getStatusColor(),
            'cover_letter' => $this->cover_letter,
            'resume_path' => $this->resume_path,
            'resume_url' => $this->when(
                $this->resume_path,
                fn() => asset('storage/' . $this->resume_path)
            ),
            
            'timeline' => [
                'applied_at' => $this->applied_at?->toISOString(),
                'applied_ago' => $this->applied_at?->diffForHumans(),
                'reviewed_at' => $this->reviewed_at?->toISOString(),
                'reviewed_ago' => $this->when(
                    $this->reviewed_at,
                    fn() => $this->reviewed_at->diffForHumans()
                ),
                'days_since_applied' => $this->applied_at?->diffInDays(now()) ?? 0,
                'response_time' => $this->when(
                    $this->reviewed_at,
                    fn() => $this->applied_at->diffInDays($this->reviewed_at) . ' days'
                ),
                'status_history' => $this->getStatusHistory(),
            ],
            
            'job' => $this->when(
                $this->relationLoaded('jobPosting'),
                fn() => new JobResource($this->jobPosting)
            ),
            
            'candidate' => $this->when(
                $this->relationLoaded('user'),
                fn() => new UserResource($this->user)
            ),
            
            'matching' => $this->when(
                $this->relationLoaded('jobPosting.skills', 'user.skills'),
                fn() => [
                    'skill_match_percentage' => $this->calculateSkillMatch(),
                    'matched_skills' => $this->getMatchedSkills(),
                    'missing_skills' => $this->getMissingSkills(),
                    'location_match' => $this->checkLocationMatch(),
                    'salary_match' => $this->checkSalaryMatch(),
                    'experience_match' => $this->checkExperienceMatch(),
                ]
            ),
            
            'feedback' => [
                'employer_notes' => $this->employer_notes,
                'interview_feedback' => $this->interview_feedback,
                'rejection_reason' => $this->rejection_reason,
                'next_steps' => $this->getNextSteps(),
            ],
            
            'actions' => [
                'can_withdraw' => $this->canWithdraw(),
                'can_update_status' => $this->canUpdateStatus(),
                'can_schedule_interview' => $this->canScheduleInterview(),
                'can_send_message' => true,
                'available_status_changes' => $this->getAvailableStatusChanges(),
            ],
            
            'meta' => [
                'is_recent' => $this->applied_at?->isAfter(now()->subDays(7)) ?? false,
                'needs_review' => $this->status === 'pending' && $this->applied_at?->isBefore(now()->subDays(3)),
                'urgency_level' => $this->getUrgencyLevel(),
                'recommendation' => $this->getRecommendation(),
                'quality_score' => $this->calculateQualityScore(),
            ],
        ];
    }

    /**
     * Get status display name
     */
    private function getStatusDisplay(): string
    {
        return match($this->status) {
            'pending' => 'Under Review',
            'shortlisted' => 'Shortlisted',
            'interviewed' => 'Interviewed',
            'hired' => 'Hired',
            'rejected' => 'Not Selected',
            'withdrawn' => 'Withdrawn',
            default => ucfirst($this->status)
        };
    }

    /**
     * Get status color for UI
     */
    private function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'shortlisted' => 'info',
            'interviewed' => 'primary',
            'hired' => 'success',
            'rejected' => 'danger',
            'withdrawn' => 'secondary',
            default => 'light'
        };
    }

    /**
     * Calculate skill match percentage
     */
    private function calculateSkillMatch(): int
    {
        if (!$this->relationLoaded('jobPosting.skills', 'user.skills')) {
            return 0;
        }

        $jobSkills = $this->jobPosting->skills->pluck('id')->toArray();
        $userSkills = $this->user->skills->pluck('id')->toArray();
        
        if (empty($jobSkills)) return 100;
        
        $matchedSkills = array_intersect($jobSkills, $userSkills);
        return round((count($matchedSkills) / count($jobSkills)) * 100);
    }

    /**
     * Get matched skills
     */
    private function getMatchedSkills(): array
    {
        if (!$this->relationLoaded('jobPosting.skills', 'user.skills')) {
            return [];
        }

        $jobSkills = $this->jobPosting->skills->keyBy('id');
        $userSkills = $this->user->skills->pluck('id')->toArray();
        
        return $jobSkills->filter(fn($skill) => in_array($skill->id, $userSkills))
            ->map(fn($skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'is_required' => $skill->pivot->is_required ?? false,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get missing skills
     */
    private function getMissingSkills(): array
    {
        if (!$this->relationLoaded('jobPosting.skills', 'user.skills')) {
            return [];
        }

        $jobSkills = $this->jobPosting->skills->keyBy('id');
        $userSkills = $this->user->skills->pluck('id')->toArray();
        
        return $jobSkills->filter(fn($skill) => !in_array($skill->id, $userSkills))
            ->map(fn($skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'is_required' => $skill->pivot->is_required ?? false,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Check location match
     */
    private function checkLocationMatch(): string
    {
        if (!$this->relationLoaded('jobPosting', 'user.profile')) {
            return 'unknown';
        }

        $jobLocation = $this->jobPosting;
        $userProfile = $this->user->profile;
        
        if (!$userProfile) return 'no_profile';
        if ($jobLocation->remote_allowed) return 'remote_ok';
        
        if ($jobLocation->city_id === $userProfile->city_id) return 'exact';
        if ($jobLocation->state_id === $userProfile->state_id) return 'same_state';
        if ($jobLocation->country_id === $userProfile->country_id) return 'same_country';
        
        return 'different';
    }

    /**
     * Check salary match
     */
    private function checkSalaryMatch(): string
    {
        if (!$this->relationLoaded('jobPosting', 'user.profile')) {
            return 'unknown';
        }

        $job = $this->jobPosting;
        $profile = $this->user->profile;
        
        if (!$profile || !$profile->min_salary || !$job->min_salary) return 'no_data';
        
        if ($job->max_salary >= $profile->min_salary && $job->min_salary <= $profile->max_salary) {
            return 'match';
        }
        
        if ($job->max_salary < $profile->min_salary) return 'below_expectation';
        if ($job->min_salary > $profile->max_salary) return 'above_range';
        
        return 'partial';
    }

    /**
     * Check experience match
     */
    private function checkExperienceMatch(): string
    {
        if (!$this->relationLoaded('jobPosting', 'user.profile')) {
            return 'unknown';
        }

        $requiredYears = $this->jobPosting->experience_years ?? 0;
        // Note: User experience would need to be tracked separately or calculated from work history
        
        return 'requires_calculation'; // Placeholder for actual implementation
    }

    /**
     * Get status history (placeholder - would need status_changes table)
     */
    private function getStatusHistory(): array
    {
        return [
            [
                'status' => 'pending',
                'changed_at' => $this->applied_at?->toISOString(),
                'changed_by' => 'system',
                'note' => 'Application submitted',
            ],
            // Additional status changes would be loaded from status_changes table
        ];
    }

    /**
     * Get next steps based on current status
     */
    private function getNextSteps(): ?string
    {
        return match($this->status) {
            'pending' => 'Application is under review. You will be notified of any updates.',
            'shortlisted' => 'Congratulations! You have been shortlisted. Please wait for interview scheduling.',
            'interviewed' => 'Interview completed. Awaiting final decision.',
            'hired' => 'Congratulations! Please check your email for next steps.',
            'rejected' => 'Thank you for your interest. Keep applying for other opportunities.',
            'withdrawn' => 'Application withdrawn by candidate.',
            default => null
        };
    }

    /**
     * Check if application can be withdrawn
     */
    private function canWithdraw(): bool
    {
        return in_array($this->status, ['pending', 'shortlisted', 'interviewed']);
    }

    /**
     * Check if status can be updated (for employers)
     */
    private function canUpdateStatus(): bool
    {
        return !in_array($this->status, ['withdrawn', 'hired']);
    }

    /**
     * Check if interview can be scheduled
     */
    private function canScheduleInterview(): bool
    {
        return $this->status === 'shortlisted';
    }

    /**
     * Get available status changes for current status
     */
    private function getAvailableStatusChanges(): array
    {
        return match($this->status) {
            'pending' => ['shortlisted', 'rejected'],
            'shortlisted' => ['interviewed', 'rejected'],
            'interviewed' => ['hired', 'rejected'],
            default => []
        };
    }

    /**
     * Get urgency level for employers
     */
    private function getUrgencyLevel(): string
    {
        if ($this->status !== 'pending') return 'none';
        
        $daysSinceApplied = $this->applied_at?->diffInDays(now()) ?? 0;
        
        return match(true) {
            $daysSinceApplied >= 7 => 'high',
            $daysSinceApplied >= 3 => 'medium',
            default => 'low'
        };
    }

    /**
     * Get recommendation for employer
     */
    private function getRecommendation(): ?string
    {
        $skillMatch = $this->calculateSkillMatch();
        
        if ($skillMatch >= 80) return 'highly_recommended';
        if ($skillMatch >= 60) return 'recommended';
        if ($skillMatch >= 40) return 'consider';
        
        return 'review_carefully';
    }

    /**
     * Calculate overall quality score
     */
    private function calculateQualityScore(): int
    {
        $factors = [];
        
        // Skill match (40% weight)
        $factors['skills'] = $this->calculateSkillMatch() * 0.4;
        
        // Profile completion (20% weight)
        $profileComplete = $this->user->profile?->isComplete() ?? false;
        $factors['profile'] = ($profileComplete ? 100 : 50) * 0.2;
        
        // Cover letter quality (20% weight)
        $coverLetterLength = strlen($this->cover_letter ?? '');
        $factors['cover_letter'] = min(100, ($coverLetterLength / 300) * 100) * 0.2;
        
        // Application timeliness (20% weight)
        $daysSincePosted = $this->jobPosting->created_at?->diffInDays($this->applied_at) ?? 0;
        $factors['timeliness'] = max(0, 100 - ($daysSincePosted * 5)) * 0.2;
        
        return round(array_sum($factors));
    }
} 