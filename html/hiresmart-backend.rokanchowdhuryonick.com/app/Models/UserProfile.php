<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'phone',
        'resume_path',
        'min_salary',
        'max_salary',
        'currency',
        'country_id',
        'state_id',
        'city_id',
        'area_id',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * User who owns this profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User's skills (through user_skills pivot)
     */
    public function skills(): BelongsToMany
    {
        return $this->user->skills();
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
        if ($minSalary) {
            $query->where('max_salary', '>=', $minSalary);
        }
        if ($maxSalary) {
            $query->where('min_salary', '<=', $maxSalary);
        }
        return $query;
    }

    /**
     * Scope: Profiles with skills
     */
    public function scopeWithSkills($query, array $skillIds = [])
    {
        if (empty($skillIds)) {
            return $query->whereHas('user.skills');
        }
        
        return $query->whereHas('user.skills', function ($q) use ($skillIds) {
            $q->whereIn('skills.id', $skillIds);
        });
    }

    /**
     * Scope: Profiles with resume
     */
    public function scopeWithResume($query)
    {
        return $query->whereNotNull('resume_path');
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
     * Check if profile has resume
     */
    public function hasResume(): bool
    {
        return !empty($this->resume_path);
    }

    /**
     * Check if profile is complete
     */
    public function isComplete(): bool
    {
        $requiredFields = [
            'bio',
            'phone',
            'country_id',
            'state_id',
            'city_id',
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }
        
        // Should have at least one skill
        return $this->user->skills()->exists();
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): int
    {
        $fields = [
            'bio' => 20,
            'phone' => 10,
            'resume_path' => 20,
            'country_id' => 10,
            'state_id' => 10,
            'city_id' => 10,
            'min_salary' => 10,
            'max_salary' => 10,
        ];
        
        $totalScore = 0;
        foreach ($fields as $field => $score) {
            if (!empty($this->$field)) {
                $totalScore += $score;
            }
        }
        
        // Bonus 10% for having skills
        if ($this->user && $this->user->skills()->exists()) {
            $totalScore += 10;
        }
        
        return min(100, $totalScore);
    }
}
