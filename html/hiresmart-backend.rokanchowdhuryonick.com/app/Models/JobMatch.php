<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'candidate_id',
        'match_score',
        'match_reasons',
        'notification_sent',
    ];

    protected $casts = [
        'match_score' => 'decimal:4',
        'match_reasons' => 'array',
        'notification_sent' => 'boolean',
        'created_at' => 'datetime',
    ];

    // No updated_at as matches are immutable once created
    public $timestamps = ['created_at'];
    const UPDATED_AT = null;

    /**
     * Job posting that was matched
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    /**
     * Candidate who was matched (user)
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /**
     * Scope: High quality matches (score >= 0.7)
     */
    public function scopeHighQuality($query)
    {
        return $query->where('match_score', '>=', 0.7);
    }

    /**
     * Scope: Medium quality matches (score 0.5-0.7)
     */
    public function scopeMediumQuality($query)
    {
        return $query->whereBetween('match_score', [0.5, 0.7]);
    }

    /**
     * Scope: Low quality matches (score < 0.5)
     */
    public function scopeLowQuality($query)
    {
        return $query->where('match_score', '<', 0.5);
    }

    /**
     * Scope: Filter by minimum match score
     */
    public function scopeMinScore($query, $minScore)
    {
        return $query->where('match_score', '>=', $minScore);
    }

    /**
     * Scope: For specific job posting
     */
    public function scopeForJob($query, $jobPostingId)
    {
        return $query->where('job_posting_id', $jobPostingId);
    }

    /**
     * Scope: For specific candidate
     */
    public function scopeForCandidate($query, $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    /**
     * Scope: Notification not sent yet
     */
    public function scopeNotificationPending($query)
    {
        return $query->where('notification_sent', false);
    }

    /**
     * Scope: Recent matches
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Send notification to candidate about this match
     */
    public function sendNotification()
    {
        if (!$this->notification_sent && $this->match_score >= 0.5) {
            $matchPercentage = round($this->match_score * 100);
            
            Notification::createJobMatch(
                $this->candidate_id,
                $this->jobPosting->title,
                $matchPercentage
            );
            
            $this->update(['notification_sent' => true]);
        }
    }

    /**
     * Get match score as percentage
     */
    public function getMatchPercentageAttribute(): int
    {
        return round($this->match_score * 100);
    }

    /**
     * Get match quality level
     */
    public function getMatchQualityAttribute(): string
    {
        if ($this->match_score >= 0.8) {
            return 'Excellent';
        } elseif ($this->match_score >= 0.7) {
            return 'Very Good';
        } elseif ($this->match_score >= 0.6) {
            return 'Good';
        } elseif ($this->match_score >= 0.5) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    /**
     * Get match quality color for UI
     */
    public function getMatchColorAttribute(): string
    {
        if ($this->match_score >= 0.8) {
            return 'green';
        } elseif ($this->match_score >= 0.7) {
            return 'blue';
        } elseif ($this->match_score >= 0.6) {
            return 'yellow';
        } elseif ($this->match_score >= 0.5) {
            return 'orange';
        } else {
            return 'red';
        }
    }

    /**
     * Get formatted match reasons
     */
    public function getFormattedReasonsAttribute(): array
    {
        $reasons = $this->match_reasons ?? [];
        $formatted = [];
        
        foreach ($reasons as $key => $score) {
            $percentage = round($score * 100);
            $label = match($key) {
                'skills' => 'Skills Match',
                'location' => 'Location Match',
                'salary' => 'Salary Match',
                'experience' => 'Experience Match',
                default => ucfirst($key),
            };
            
            $formatted[] = [
                'label' => $label,
                'score' => $score,
                'percentage' => $percentage,
                'color' => $this->getScoreColor($score),
            ];
        }
        
        return $formatted;
    }

    /**
     * Get color for individual score
     */
    private function getScoreColor($score): string
    {
        if ($score >= 0.8) return 'green';
        if ($score >= 0.6) return 'blue';
        if ($score >= 0.4) return 'yellow';
        if ($score >= 0.2) return 'orange';
        return 'red';
    }

    /**
     * Static method to create a new job match
     */
    public static function createMatch($jobPostingId, $candidateId, $matchScore, $matchReasons = [])
    {
        // Prevent duplicate matches
        $existing = static::where('job_posting_id', $jobPostingId)
                          ->where('candidate_id', $candidateId)
                          ->first();
        
        if ($existing) {
            return $existing;
        }
        
        return static::create([
            'job_posting_id' => $jobPostingId,
            'candidate_id' => $candidateId,
            'match_score' => $matchScore,
            'match_reasons' => $matchReasons,
            'notification_sent' => false,
        ]);
    }

    /**
     * Check if candidate has already applied to this job
     */
    public function candidateHasApplied(): bool
    {
        return Application::where('job_posting_id', $this->job_posting_id)
                         ->where('user_id', $this->candidate_id)
                         ->exists();
    }
}
