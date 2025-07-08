<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
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
                'range_display' => $this->when(
                    $this->min_salary || $this->max_salary,
                    fn() => $this->getSalaryRangeDisplay()
                ),
            ],
            'experience_required' => [
                'years' => $this->experience_years,
                'level' => $this->getExperienceLevel(),
            ],
            'location' => [
                'country_id' => $this->country_id,
                'state_id' => $this->state_id,
                'city_id' => $this->city_id,
                'area_id' => $this->area_id,
                'remote_allowed' => $this->remote_allowed ?? false,
                'display' => $this->when(
                    $this->relationLoaded('country', 'state', 'city'),
                    fn() => $this->getLocationDisplay()
                ),
            ],
            'company' => $this->when(
                $this->relationLoaded('company'),
                fn() => new CompanyResource($this->company)
            ),
            'skills' => $this->when(
                $this->relationLoaded('skills'),
                fn() => SkillResource::collection($this->skills)
            ),
            'posting_details' => [
                'posted_at' => $this->created_at?->toISOString(),
                'posted_ago' => $this->created_at?->diffForHumans(),
                'deadline' => $this->deadline?->toISOString(),
                'days_remaining' => $this->when(
                    $this->deadline,
                    fn() => $this->deadline->diffInDays(now(), false)
                ),
                'is_expired' => $this->isExpired(),
                'is_recent' => $this->created_at?->isAfter(now()->subDays(7)) ?? false,
            ],
            'stats' => $this->when(
                $this->relationLoaded('applications'),
                fn() => [
                    'applications_count' => $this->applications->count(),
                    'views_count' => $this->views_count ?? 0,
                ]
            ),
        ];
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