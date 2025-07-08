<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Users who have this skill (many-to-many)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')
                    ->withPivot(['proficiency_level', 'years_of_experience'])
                    ->withTimestamps();
    }

    /**
     * Job postings that require this skill (many-to-many)
     */
    public function jobPostings(): BelongsToMany
    {
        return $this->belongsToMany(JobPosting::class, 'job_skills', 'skill_id', 'job_posting_id')
                    ->withPivot(['is_required'])
                    ->withTimestamps();
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'ILIKE', "%{$term}%");
    }

    /**
     * Get popular skills (most used by users)
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('users')
                    ->orderBy('users_count', 'desc')
                    ->limit($limit);
    }
}
