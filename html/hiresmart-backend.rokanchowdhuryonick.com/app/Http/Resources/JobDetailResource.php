<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobDetailResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'employment_type' => $this->employment_type,
            'employment_type_display' => $this->getEmploymentTypeDisplay(),
            'status' => $this->status,
            'status_display' => ucfirst($this->status),
            
            'salary' => [
                'min' => $this->min_salary,
                'max' => $this->max_salary,
                'currency' => $this->currency ?? 'USD',
                'formatted' => $this->when(
                    $this->min_salary && $this->max_salary,
                    fn() => sprintf(
                        '%s %s - %s',
                        $this->currency ?? 'USD',
                        number_format($this->min_salary),
                        number_format($this->max_salary)
                    )
                ),
                'range_display' => $this->getSalaryRangeDisplay(),
            ],
            
            'experience_required' => [
                'years' => $this->experience_years,
                'level' => $this->getExperienceLevel(),
                'description' => $this->getExperienceDescription(),
            ],
            
            'location' => [
                'country_id' => $this->country_id,
                'state_id' => $this->state_id,
                'city_id' => $this->city_id,
                'area_id' => $this->area_id,
                'remote_allowed' => $this->remote_allowed ?? false,
                'full_address' => $this->when(
                    $this->relationLoaded('country', 'state', 'city', 'area'),
                    fn() => $this->getLocationDisplay()
                ),
                'country' => $this->when($this->relationLoaded('country'), fn() => $this->country?->name),
                'state' => $this->when($this->relationLoaded('state'), fn() => $this->state?->name),
                'city' => $this->when($this->relationLoaded('city'), fn() => $this->city?->name),
                'area' => $this->when($this->relationLoaded('area'), fn() => $this->area?->name),
            ],
            
            'company' => $this->when(
                $this->relationLoaded('company'),
                fn() => new CompanyResource($this->company)
            ),
            
            'skills' => [
                'required' => $this->when(
                    $this->relationLoaded('skills'),
                    fn() => SkillResource::collection($this->skills->where('pivot.is_required', true))
                ),
                'preferred' => $this->when(
                    $this->relationLoaded('skills'),
                    fn() => SkillResource::collection($this->skills->where('pivot.is_required', false))
                ),
                'all' => $this->when(
                    $this->relationLoaded('skills'),
                    fn() => SkillResource::collection($this->skills)
                ),
            ],
            
            'timeline' => [
                'posted_at' => $this->created_at?->toISOString(),
                'posted_ago' => $this->created_at?->diffForHumans(),
                'deadline' => $this->deadline?->toISOString(),
                'deadline_human' => $this->deadline?->diffForHumans(),
                'days_remaining' => $this->when(
                    $this->deadline,
                    fn() => max(0, $this->deadline->diffInDays(now(), false))
                ),
                'is_expired' => $this->isExpired(),
                'is_recent' => $this->created_at?->isAfter(now()->subDays(7)) ?? false,
                'is_urgent' => $this->when(
                    $this->deadline,
                    fn() => $this->deadline->diffInDays(now()) <= 3
                ),
                'updated_at' => $this->updated_at?->toISOString(),
                'archived_at' => $this->archived_at?->toISOString(),
            ],
            
            'applications' => $this->when(
                $this->relationLoaded('applications'),
                fn() => [
                    'total_count' => $this->applications->count(),
                    'by_status' => $this->getApplicationsByStatus(),
                    'recent_applications' => ApplicationResource::collection(
                        $this->applications->take(5)->sortByDesc('applied_at')
                    ),
                ]
            ),
            
            'employer' => $this->when(
                $this->relationLoaded('employer'),
                fn() => new UserResource($this->employer)
            ),
            
            'stats' => [
                'views_count' => $this->views_count ?? 0,
                'applications_count' => $this->applications_count ?? $this->applications->count() ?? 0,
                'bookmark_count' => $this->bookmarks_count ?? 0,
                'engagement_score' => $this->calculateEngagementScore(),
            ],
            
            'seo' => [
                'slug' => $this->getSlug(),
                'meta_title' => $this->title . ' - ' . ($this->company?->name ?? 'HireSmart'),
                'meta_description' => substr(strip_tags($this->description), 0, 160),
            ],
        ];
    }

    /**
     * Get applications grouped by status
     */
    private function getApplicationsByStatus(): array
    {
        if (!$this->relationLoaded('applications')) {
            return [];
        }

        return $this->applications
            ->groupBy('status')
            ->map(fn($apps) => $apps->count())
            ->toArray();
    }

    /**
     * Calculate engagement score
     */
    private function calculateEngagementScore(): float
    {
        $views = $this->views_count ?? 0;
        $applications = $this->applications_count ?? $this->applications->count() ?? 0;
        $bookmarks = $this->bookmarks_count ?? 0;
        
        if ($views === 0) return 0;
        
        // Engagement score: (applications * 10 + bookmarks * 5) / views * 100
        return round((($applications * 10 + $bookmarks * 5) / $views) * 100, 2);
    }

    /**
     * Get job slug for SEO
     */
    private function getSlug(): string
    {
        return strtolower(str_replace(' ', '-', $this->title)) . '-' . $this->id;
    }

    /**
     * Get employment type display name
     */
    private function getEmploymentTypeDisplay(): string
    {
        return match($this->employment_type) {
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract' => 'Contract',
            'internship' => 'Internship',
            default => ucfirst(str_replace('_', ' ', $this->employment_type))
        };
    }

    /**
     * Get experience level based on years
     */
    private function getExperienceLevel(): string
    {
        if (!$this->experience_years) return 'Not specified';
        
        return match(true) {
            $this->experience_years === 0 => 'Entry Level',
            $this->experience_years <= 2 => 'Junior',
            $this->experience_years <= 5 => 'Mid Level',
            $this->experience_years <= 10 => 'Senior',
            default => 'Lead/Expert'
        };
    }

    /**
     * Get experience description
     */
    private function getExperienceDescription(): string
    {
        if (!$this->experience_years) return 'No specific experience required';
        if ($this->experience_years === 0) return 'Entry level position, no prior experience required';
        
        $years = $this->experience_years;
        return "Minimum {$years} " . ($years === 1 ? 'year' : 'years') . ' of relevant experience required';
    }

    /**
     * Get salary range display
     */
    private function getSalaryRangeDisplay(): string
    {
        $currency = $this->currency ?? 'USD';
        
        if ($this->min_salary && $this->max_salary) {
            return sprintf('%s %s - %s', $currency, number_format($this->min_salary), number_format($this->max_salary));
        }
        
        if ($this->min_salary) {
            return sprintf('%s %s+', $currency, number_format($this->min_salary));
        }
        
        if ($this->max_salary) {
            return sprintf('Up to %s %s', $currency, number_format($this->max_salary));
        }
        
        return 'Negotiable';
    }

    /**
     * Get location display string
     */
    private function getLocationDisplay(): string
    {
        return collect([
            $this->area?->name,
            $this->city?->name,
            $this->state?->name,
            $this->country?->name
        ])->filter()->implode(', ');
    }
} 