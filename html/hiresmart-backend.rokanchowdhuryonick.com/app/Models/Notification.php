<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPES = [
        'job_match' => 'Job Match',
        'application_status' => 'Application Status',
        'system' => 'System Notification',
        'job_posted' => 'Job Posted',
        'profile_incomplete' => 'Profile Incomplete',
    ];

    /**
     * User who receives this notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Recent notifications
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: For specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Get time since notification created
     */
    public function getTimeAgoAttribute(): string
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    /**
     * Get notification icon based on type
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'job_match' => '🎯',
            'application_status' => '📝',
            'system' => '🔔',
            'job_posted' => '💼',
            'profile_incomplete' => '⚠️',
            default => '📢',
        };
    }

    /**
     * Get notification color based on type
     */
    public function getColorAttribute(): string
    {
        return match($this->type) {
            'job_match' => 'green',
            'application_status' => 'blue',
            'system' => 'gray',
            'job_posted' => 'purple',
            'profile_incomplete' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Static method to create job match notification
     */
    public static function createJobMatch($userId, $jobTitle, $matchScore)
    {
        return static::create([
            'user_id' => $userId,
            'type' => 'job_match',
            'title' => 'New Job Match Found!',
            'message' => "We found a {$matchScore}% match for '{$jobTitle}'. Check it out!",
        ]);
    }

    /**
     * Static method to create application status notification
     */
    public static function createApplicationStatus($userId, $jobTitle, $status)
    {
        $statusMessages = [
            'reviewed' => "Your application for '{$jobTitle}' has been reviewed.",
            'shortlisted' => "Great news! You've been shortlisted for '{$jobTitle}'.",
            'rejected' => "Thank you for your interest in '{$jobTitle}'. Unfortunately, you were not selected.",
            'hired' => "Congratulations! You've been hired for '{$jobTitle}'.",
        ];

        return static::create([
            'user_id' => $userId,
            'type' => 'application_status',
            'title' => 'Application Update',
            'message' => $statusMessages[$status] ?? "Your application status has been updated.",
        ]);
    }

    /**
     * Static method to create system notification
     */
    public static function createSystem($userId, $title, $message)
    {
        return static::create([
            'user_id' => $userId,
            'type' => 'system',
            'title' => $title,
            'message' => $message,
        ]);
    }
}
