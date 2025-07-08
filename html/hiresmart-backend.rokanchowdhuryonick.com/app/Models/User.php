<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    const ROLES = [
        'admin' => 'Administrator',
        'employer' => 'Employer',
        'candidate' => 'Candidate',
    ];

    /**
     * User profile (one-to-one for candidates)
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Companies owned by this user (for employers)
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'user_id');
    }

    /**
     * Job postings created by this user (for employers)
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class, 'user_id');
    }

    /**
     * Applications submitted by this user (for candidates)
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'user_id');
    }

    /**
     * Skills possessed by this user (many-to-many for candidates)
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
                    ->withPivot(['proficiency_level', 'years_of_experience'])
                    ->withTimestamps();
    }

    /**
     * Notifications for this user
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Job matches for this user (for candidates)
     */
    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatch::class, 'candidate_id');
    }

    /**
     * Unread notifications
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->unread();
    }

    /**
     * Scope: Filter by role
     */
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: Active users only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Admins
     */
    public function scopeAdmins($query)
    {
        return $query->role('admin');
    }

    /**
     * Scope: Employers
     */
    public function scopeEmployers($query)
    {
        return $query->role('employer');
    }

    /**
     * Scope: Candidates
     */
    public function scopeCandidates($query)
    {
        return $query->role('candidate');
    }

    /**
     * Scope: Verified users
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope: Recent registrations
     */
    public function scopeRecentRegistrations($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is employer
     */
    public function isEmployer(): bool
    {
        return $this->role === 'employer';
    }

    /**
     * Check if user is candidate
     */
    public function isCandidate(): bool
    {
        return $this->role === 'candidate';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if user email is verified
     */
    public function isVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get user's primary company (for employers)
     */
    public function getPrimaryCompany(): ?Company
    {
        return $this->companies()->first();
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->unreadNotifications()->count();
    }

    /**
     * Get recent job matches (for candidates)
     */
    public function getRecentJobMatches($days = 7)
    {
        return $this->jobMatches()
                   ->with(['jobPosting.company'])
                   ->recent($days)
                   ->highQuality()
                   ->orderBy('match_score', 'desc')
                   ->get();
    }

    /**
     * Get application statistics (for candidates)
     */
    public function getApplicationStatsAttribute(): array
    {
        if (!$this->isCandidate()) {
            return [];
        }

        // Fix N+1: Single aggregated query instead of multiple count queries
        $stats = $this->applications()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as reviewed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as shortlisted,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hired
            ', ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'])
            ->first();
        
        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'reviewed' => (int) $stats->reviewed,
            'shortlisted' => (int) $stats->shortlisted,
            'rejected' => (int) $stats->rejected,
            'hired' => (int) $stats->hired,
        ];
    }

    /**
     * Get job posting statistics (for employers)
     */
    public function getJobStatsAttribute(): array
    {
        if (!$this->isEmployer()) {
            return [];
        }

        $jobPostings = $this->jobPostings();
        
        return [
            'total' => $jobPostings->count(),
            'active' => $jobPostings->active()->count(),
            'closed' => $jobPostings->where('status', 'closed')->count(),
            'draft' => $jobPostings->where('status', 'draft')->count(),
            'archived' => $jobPostings->where('status', 'archived')->count(),
        ];
    }

    /**
     * Create or get user profile (for candidates)
     */
    public function getOrCreateProfile(): UserProfile
    {
        return $this->profile ?: $this->profile()->create([]);
    }

    /**
     * Add skill to user
     */
    public function addSkill($skillId, $proficiencyLevel = 'beginner', $yearsOfExperience = 0)
    {
        return $this->skills()->syncWithoutDetaching([
            $skillId => [
                'proficiency_level' => $proficiencyLevel,
                'years_of_experience' => $yearsOfExperience,
            ]
        ]);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
