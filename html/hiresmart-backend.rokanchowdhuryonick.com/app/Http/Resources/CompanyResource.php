<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'description' => $this->description,
            'website' => $this->website,
            'logo_url' => $this->logo_url,
            'industry' => $this->industry,
            'size' => $this->size,
            'founded_year' => $this->founded_year,
            'location' => [
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
            ],
            'contact' => [
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
            ],
            'stats' => $this->when(
                $this->relationLoaded('jobPostings'),
                fn() => [
                    'total_jobs' => $this->jobPostings->count(),
                    'active_jobs' => $this->jobPostings->where('status', 'active')->count(),
                ]
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
} 