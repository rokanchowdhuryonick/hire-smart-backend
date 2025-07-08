<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'user_id', // candidate
        'cover_letter',
        'resume_path',
        'status',
        'applied_at',
        'reviewed_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUSES = [
        'pending' => 'Pending Review',
        'reviewed' => 'Reviewed',
        'shortlisted' => 'Shortlisted',
        'rejected' => 'Rejected',
        'hired' => 'Hired',
    ];

    /**
     * Job posting this application is for
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    /**
     * Candidate who applied (user)
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending applications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Reviewed applications
     */
    public function scopeReviewed($query)
    {
        return $query->whereIn('status', ['reviewed', 'shortlisted', 'rejected', 'hired']);
    }

    /**
     * Scope: For a specific job posting
     */
    public function scopeForJob($query, $jobPostingId)
    {
        return $query->where('job_posting_id', $jobPostingId);
    }

    /**
     * Scope: For a specific candidate
     */
    public function scopeForCandidate($query, $candidateId)
    {
        return $query->where('user_id', $candidateId);
    }

    /**
     * Scope: Recent applications
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('applied_at', '>=', now()->subDays($days));
    }

    /**
     * Mark application as reviewed
     */
    public function markAsReviewed()
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Shortlist application
     */
    public function shortlist()
    {
        $this->update([
            'status' => 'shortlisted',
            'reviewed_at' => $this->reviewed_at ?: now(),
        ]);
    }

    /**
     * Reject application
     */
    public function reject()
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_at' => $this->reviewed_at ?: now(),
        ]);
    }

    /**
     * Hire candidate
     */
    public function hire()
    {
        $this->update([
            'status' => 'hired',
            'reviewed_at' => $this->reviewed_at ?: now(),
        ]);
    }

    /**
     * Check if application is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if application was reviewed
     */
    public function isReviewed(): bool
    {
        return !$this->isPending();
    }

    /**
     * Get days since application
     */
    public function getDaysSinceApplicationAttribute(): int
    {
        return $this->applied_at->diffInDays(now());
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'reviewed' => 'blue', 
            'shortlisted' => 'green',
            'rejected' => 'red',
            'hired' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Boot method to set applied_at automatically
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($application) {
            if (!$application->applied_at) {
                $application->applied_at = now();
            }
        });
    }
}
