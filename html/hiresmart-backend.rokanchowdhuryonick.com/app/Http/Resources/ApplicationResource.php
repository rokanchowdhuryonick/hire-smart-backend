<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
                    fn() => $this->applied_at->diffInDays($this->reviewed_at)
                ),
            ],
            
            'job' => $this->when(
                $this->relationLoaded('jobPosting'),
                fn() => [
                    'id' => $this->jobPosting->id,
                    'title' => $this->jobPosting->title,
                    'employment_type' => $this->jobPosting->employment_type,
                    'status' => $this->jobPosting->status,
                    'company_name' => $this->jobPosting->company?->name ?? 'Unknown',
                    'location' => $this->when(
                        $this->jobPosting->relationLoaded('city', 'state', 'country'),
                        fn() => collect([
                            $this->jobPosting->area?->name,
                            $this->jobPosting->city?->name,
                            $this->jobPosting->state?->name,
                            $this->jobPosting->country?->name
                        ])->filter()->implode(', ')
                    ),
                ]
            ),
            
            'candidate' => $this->when(
                $this->relationLoaded('user'),
                fn() => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'profile_completed' => $this->user->profile?->isComplete() ?? false,
                ]
            ),
            
            'meta' => [
                'can_withdraw' => $this->canWithdraw(),
                'is_recent' => $this->applied_at?->isAfter(now()->subDays(7)) ?? false,
                'needs_review' => $this->status === 'pending' && $this->applied_at?->isBefore(now()->subDays(3)),
                'urgency_level' => $this->getUrgencyLevel(),
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
     * Check if application can be withdrawn
     */
    private function canWithdraw(): bool
    {
        return in_array($this->status, ['pending', 'shortlisted', 'interviewed']);
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
} 