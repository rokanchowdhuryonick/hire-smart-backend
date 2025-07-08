<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_postings';

    protected $fillable = [
        'company_id',
        'user_id', // employer
        'title',
        'description',
        'min_salary',
        'max_salary',
        'currency',
        'employment_type',
        'status',
        'deadline',
        'experience_years',
        'country_id',
        'state_id',
        'city_id',
        'area_id',
        'archived_at',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'deadline' => 'date',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const EMPLOYMENT_TYPES = [
        'full_time' => 'Full Time',
        'part_time' => 'Part Time', 
        'contract' => 'Contract',
        'internship' => 'Internship',
    ];

    const STATUSES = [
        'active' => 'Active',
        'closed' => 'Closed',
        'draft' => 'Draft',
        'archived' => 'Archived',
    ];

    /**
     * Company that posted this job
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Employer (user) who posted this job
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Applications for this job
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_posting_id');
    }

    /**
     * Skills required for this job (many-to-many)
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skills', 'job_posting_id', 'skill_id')
                    ->withPivot(['is_required'])
                    ->withTimestamps();
    }

    /**
     * Required skills only
     */
    public function requiredSkills(): BelongsToMany
    {
        return $this->skills()->wherePivot('is_required', true);
    }

    /**
     * Optional skills only
     */
    public function optionalSkills(): BelongsToMany
    {
        return $this->skills()->wherePivot('is_required', false);
    }

    /**
     * Job matches for this posting
     */
    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatch::class, 'job_posting_id');
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
     * Scope: Active jobs only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('deadline')
                          ->orWhere('deadline', '>=', now()->toDateString());
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
     * Scope: Filter by salary range
     */
    public function scopeSalaryRange($query, $minSalary = null, $maxSalary = null)
    {
        if ($minSalary && $maxSalary) {
            return $query->where(function ($q) use ($minSalary, $maxSalary) {
                $q->where('min_salary', '<=', $maxSalary)
                  ->where('max_salary', '>=', $minSalary);
            });
        }
        return $query;
    }

    /**
     * Scope: Search by title and description
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'ILIKE', "%{$term}%")
              ->orWhere('description', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Scope: Filter by employment type
     */
    public function scopeEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    /**
     * Archive job (business logic)
     */
    public function archive()
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }

    /**
     * Check if job is expired
     */
    public function isExpired(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }

    /**
     * Get formatted salary range
     */
    public function getSalaryRangeAttribute(): string
    {
        if (!$this->min_salary && !$this->max_salary) {
            return 'Negotiable';
        }
        
        $currency = $this->currency ?? 'BDT';
        
        if ($this->min_salary && $this->max_salary) {
            return "{$currency} {$this->min_salary} - {$this->max_salary}";
        }
        
        if ($this->min_salary) {
            return "{$currency} {$this->min_salary}+";
        }
        
        return "{$currency} Up to {$this->max_salary}";
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
}
