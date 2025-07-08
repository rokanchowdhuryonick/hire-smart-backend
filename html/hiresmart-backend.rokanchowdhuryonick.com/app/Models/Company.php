<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'website',
        'logo_path',
        'phone',
        'user_id', // employer
        'country_id',
        'state_id',
        'city_id',
        'area_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Employer (user) who owns this company
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Job postings by this company
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * Active job postings only
     */
    public function activeJobPostings(): HasMany
    {
        return $this->jobPostings()->active();
    }

    // Location relationships
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Scope: Search by name or description
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ILIKE', "%{$term}%")
              ->orWhere('description', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Scope: Filter by location
     */
    public function scopeLocation($query, $countryId = null, $stateId = null, $cityId = null, $areaId = null)
    {
        if ($areaId) {
            return $query->where('area_id', $areaId);
        }
        if ($cityId) {
            return $query->where('city_id', $cityId);
        }
        if ($stateId) {
            return $query->where('state_id', $stateId);
        }
        if ($countryId) {
            return $query->where('country_id', $countryId);
        }
        return $query;
    }

    /**
     * Scope: Companies with active job postings
     */
    public function scopeWithActiveJobs($query)
    {
        return $query->whereHas('jobPostings', function ($q) {
            $q->active();
        });
    }

    /**
     * Get location string
     */
    public function getLocationStringAttribute(): string
    {
        $parts = array_filter([
            $this->area?->name,
            $this->city?->name,
            $this->state?->name,
            $this->country?->name,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get total job postings count
     */
    public function getTotalJobsAttribute(): int
    {
        return $this->jobPostings()->count();
    }

    /**
     * Get active job postings count
     */
    public function getActiveJobsAttribute(): int
    {
        return $this->jobPostings()->active()->count();
    }

    /**
     * Get website domain for display
     */
    public function getWebsiteDomainAttribute(): ?string
    {
        if (!$this->website) {
            return null;
        }
        
        $parsed = parse_url($this->website);
        return $parsed['host'] ?? $this->website;
    }

    /**
     * Check if company has logo
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo_path);
    }

    /**
     * Get logo URL or default placeholder
     */
    public function getLogoUrlAttribute(): string
    {
        return $this->logo_path ?: '/default-company-logo.png';
    }
}
