<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bio' => $this->bio,
            'phone' => $this->phone,
            'salary_range' => [
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
            ],
            'location' => [
                'country_id' => $this->country_id,
                'state_id' => $this->state_id,
                'city_id' => $this->city_id,
                'area_id' => $this->area_id,
                'country' => $this->when(
                    $this->relationLoaded('country'),
                    fn() => $this->country?->name
                ),
                'state' => $this->when(
                    $this->relationLoaded('state'),
                    fn() => $this->state?->name
                ),
                'city' => $this->when(
                    $this->relationLoaded('city'),
                    fn() => $this->city?->name
                ),
                'area' => $this->when(
                    $this->relationLoaded('area'),
                    fn() => $this->area?->name
                ),
                'full_address' => $this->when(
                    $this->relationLoaded('country', 'state', 'city'),
                    fn() => collect([
                        $this->area?->name,
                        $this->city?->name,
                        $this->state?->name,
                        $this->country?->name
                    ])->filter()->implode(', ')
                ),
            ],
            'completion_status' => [
                'is_complete' => $this->isComplete(),
                'completion_percentage' => $this->getCompletionPercentage(),
                'missing_fields' => $this->getMissingFields(),
            ],
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get completion percentage
     */
    private function getCompletionPercentage(): int
    {
        $requiredFields = ['bio', 'phone', 'min_salary', 'max_salary', 'country_id', 'state_id', 'city_id', 'area_id'];
        $completedFields = 0;

        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        // Include skills in completion calculation
        $skillsCount = $this->resource->user->skills()->count() ?? 0;
        if ($skillsCount > 0) {
            $completedFields++;
        }

        return round(($completedFields / (count($requiredFields) + 1)) * 100);
    }

    /**
     * Get missing required fields
     */
    private function getMissingFields(): array
    {
        $missing = [];
        
        if (empty($this->bio)) $missing[] = 'bio';
        if (empty($this->phone)) $missing[] = 'phone';
        if (empty($this->min_salary)) $missing[] = 'min_salary';
        if (empty($this->max_salary)) $missing[] = 'max_salary';
        if (empty($this->country_id)) $missing[] = 'country';
        if (empty($this->state_id)) $missing[] = 'state';
        if (empty($this->city_id)) $missing[] = 'city';
        if (empty($this->area_id)) $missing[] = 'area';
        
        $skillsCount = $this->resource->user->skills()->count() ?? 0;
        if ($skillsCount === 0) $missing[] = 'skills';

        return $missing;
    }
} 