<?php

namespace App\Services;

use App\Models\JobPosting;
use App\Models\User;
use App\Models\JobMatch;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class MatchingService
{
    /**
     * Run job matching for all active jobs and candidates
     */
    public function runJobMatching(): array
    {
        $startTime = microtime(true);
        $matchesCreated = 0;
        $notificationsSent = 0;

        try {
            // Get all active jobs
            $activeJobs = JobPosting::active()
                ->with(['skills', 'country', 'state', 'city', 'area'])
                ->get();

            // Get all active candidates with profiles
            $candidates = User::candidates()
                ->active()
                ->whereHas('profile')
                ->with(['profile', 'skills'])
                ->get();

            Log::info("Job Matching Started", [
                'active_jobs' => $activeJobs->count(),
                'candidates' => $candidates->count()
            ]);

            foreach ($activeJobs as $job) {
                $jobMatches = $this->findMatchingCandidates($job, $candidates);
                
                foreach ($jobMatches as $matchData) {
                    $match = $this->createJobMatch($job, $matchData['candidate'], $matchData['score'], $matchData['reasons']);
                    
                    if ($match->wasRecentlyCreated) {
                        $matchesCreated++;
                        
                        // Send notification for high-quality matches
                        if ($match->match_score >= 0.7) {
                            $match->sendNotification();
                            $notificationsSent++;
                        }
                    }
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            Log::info("Job Matching Completed", [
                'duration_seconds' => $duration,
                'matches_created' => $matchesCreated,
                'notifications_sent' => $notificationsSent
            ]);

            return [
                'success' => true,
                'matches_created' => $matchesCreated,
                'notifications_sent' => $notificationsSent,
                'duration' => $duration,
            ];

        } catch (\Exception $e) {
            Log::error("Job Matching Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'matches_created' => $matchesCreated,
            ];
        }
    }

    /**
     * Find matching candidates for a specific job
     */
    public function findMatchingCandidates(JobPosting $job, Collection $candidates = null): array
    {
        if ($candidates === null) {
            $candidates = User::candidates()
                ->active()
                ->whereHas('profile')
                ->with(['profile', 'skills'])
                ->get();
        }

        $matches = [];

        foreach ($candidates as $candidate) {
            // Skip if candidate already applied to this job
            if ($this->hasAlreadyApplied($job, $candidate)) {
                continue;
            }

            $matchScore = $this->calculateMatchScore($job, $candidate);
            
            // Only consider matches with score >= 0.5 (50%)
            if ($matchScore['total_score'] >= 0.5) {
                $matches[] = [
                    'candidate' => $candidate,
                    'score' => $matchScore['total_score'],
                    'reasons' => $matchScore['breakdown'],
                ];
            }
        }

        // Sort by match score (highest first)
        usort($matches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $matches;
    }

    /**
     * Calculate match score between job and candidate
     */
    public function calculateMatchScore(JobPosting $job, User $candidate): array
    {
        $scores = [];
        $weights = [
            'skills' => 0.4,      // 40% weight
            'location' => 0.3,    // 30% weight
            'salary' => 0.2,      // 20% weight
            'experience' => 0.1,  // 10% weight
        ];

        // Skills matching
        $scores['skills'] = $this->calculateSkillsMatch($job, $candidate);

        // Location matching
        $scores['location'] = $this->calculateLocationMatch($job, $candidate);

        // Salary matching
        $scores['salary'] = $this->calculateSalaryMatch($job, $candidate);

        // Experience matching
        $scores['experience'] = $this->calculateExperienceMatch($job, $candidate);

        // Calculate weighted total score
        $totalScore = 0;
        foreach ($scores as $category => $score) {
            $totalScore += $score * $weights[$category];
        }

        return [
            'total_score' => round($totalScore, 4),
            'breakdown' => $scores,
        ];
    }

    /**
     * Calculate skills match score
     */
    private function calculateSkillsMatch(JobPosting $job, User $candidate): float
    {
        $jobSkills = $job->skills;
        $candidateSkills = $candidate->skills;

        if ($jobSkills->isEmpty()) {
            return 1.0; // If no skills required, perfect match
        }

        if ($candidateSkills->isEmpty()) {
            return 0.0; // If candidate has no skills, no match
        }

        $requiredSkills = $jobSkills->where('pivot.is_required', true);
        $optionalSkills = $jobSkills->where('pivot.is_required', false);

        $candidateSkillIds = $candidateSkills->pluck('id')->toArray();

        // Check required skills (must have all)
        $requiredMatches = 0;
        foreach ($requiredSkills as $skill) {
            if (in_array($skill->id, $candidateSkillIds)) {
                $requiredMatches++;
            }
        }

        // If not all required skills are met, low score
        if ($requiredSkills->isNotEmpty() && $requiredMatches < $requiredSkills->count()) {
            return 0.2; // Low score for missing required skills
        }

        // Check optional skills
        $optionalMatches = 0;
        foreach ($optionalSkills as $skill) {
            if (in_array($skill->id, $candidateSkillIds)) {
                $optionalMatches++;
            }
        }

        // Calculate overall skills score
        $requiredScore = $requiredSkills->isEmpty() ? 1.0 : 1.0; // Perfect if all required skills met
        $optionalScore = $optionalSkills->isEmpty() ? 1.0 : ($optionalMatches / $optionalSkills->count());

        // Weight: 70% required skills, 30% optional skills
        return ($requiredScore * 0.7) + ($optionalScore * 0.3);
    }

    /**
     * Calculate location match score
     */
    private function calculateLocationMatch(JobPosting $job, User $candidate): float
    {
        $profile = $candidate->profile;

        if (!$profile) {
            return 0.0;
        }

        // Exact area match
        if ($job->area_id && $profile->area_id === $job->area_id) {
            return 1.0;
        }

        // Same city
        if ($job->city_id && $profile->city_id === $job->city_id) {
            return 0.9;
        }

        // Same state
        if ($job->state_id && $profile->state_id === $job->state_id) {
            return 0.7;
        }

        // Same country
        if ($job->country_id && $profile->country_id === $job->country_id) {
            return 0.5;
        }

        // Different country
        return 0.0;
    }

    /**
     * Calculate salary match score
     */
    private function calculateSalaryMatch(JobPosting $job, User $candidate): float
    {
        $profile = $candidate->profile;

        if (!$profile || !$profile->min_salary || !$profile->max_salary) {
            return 0.5; // Neutral if no salary preferences
        }

        if (!$job->min_salary || !$job->max_salary) {
            return 0.5; // Neutral if job has no salary range
        }

        // Check if ranges overlap
        $jobMin = $job->min_salary;
        $jobMax = $job->max_salary;
        $candidateMin = $profile->min_salary;
        $candidateMax = $profile->max_salary;

        // No overlap
        if ($jobMax < $candidateMin || $candidateMax < $jobMin) {
            return 0.0;
        }

        // Calculate overlap percentage
        $overlapMin = max($jobMin, $candidateMin);
        $overlapMax = min($jobMax, $candidateMax);
        $overlapSize = $overlapMax - $overlapMin;

        $jobRange = $jobMax - $jobMin;
        $candidateRange = $candidateMax - $candidateMin;

        if ($jobRange <= 0 || $candidateRange <= 0) {
            return 1.0; // Perfect match if either range is a single value
        }

        // Score based on overlap size relative to ranges
        $overlapScore = min(
            $overlapSize / $jobRange,
            $overlapSize / $candidateRange
        );

        return max(0.0, min(1.0, $overlapScore));
    }

    /**
     * Calculate experience match score
     */
    private function calculateExperienceMatch(JobPosting $job, User $candidate): float
    {
        if (!$job->experience_years) {
            return 1.0; // No experience requirement
        }

        $candidateSkills = $candidate->skills;
        if ($candidateSkills->isEmpty()) {
            return 0.0;
        }

        // Get average years of experience from candidate's skills
        $totalExperience = $candidateSkills->sum('pivot.years_of_experience');
        $avgExperience = $totalExperience / $candidateSkills->count();

        if ($avgExperience >= $job->experience_years) {
            return 1.0; // Meets or exceeds requirement
        }

        // Partial score based on how close they are
        return max(0.0, $avgExperience / $job->experience_years);
    }

    /**
     * Create job match record
     */
    private function createJobMatch(JobPosting $job, User $candidate, float $score, array $reasons): JobMatch
    {
        return JobMatch::firstOrCreate(
            [
                'job_posting_id' => $job->id,
                'candidate_id' => $candidate->id,
            ],
            [
                'match_score' => $score,
                'match_reasons' => $reasons,
                'notification_sent' => false,
            ]
        );
    }

    /**
     * Check if candidate has already applied to the job
     */
    private function hasAlreadyApplied(JobPosting $job, User $candidate): bool
    {
        return $job->applications()
            ->where('user_id', $candidate->id)
            ->exists();
    }

    /**
     * Get matches for a specific candidate
     */
    public function getCandidateMatches(User $candidate, int $minScore = 70): Collection
    {
        if (!$candidate->isCandidate()) {
            throw new \Exception('Only candidates can view job matches', 403);
        }

        return JobMatch::where('candidate_id', $candidate->id)
            ->where('match_score', '>=', $minScore / 100)
            ->with(['jobPosting.company', 'jobPosting.skills'])
            ->orderBy('match_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get matches for a specific job
     */
    public function getJobMatches(JobPosting $job, User $employer, int $minScore = 70): Collection
    {
        // Check ownership
        if ($job->user_id !== $employer->id && !$employer->isAdmin()) {
            throw new \Exception('Unauthorized to view job matches', 403);
        }

        return JobMatch::where('job_posting_id', $job->id)
            ->where('match_score', '>=', $minScore / 100)
            ->with(['candidate.profile', 'candidate.skills'])
            ->orderBy('match_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark match notification as sent
     */
    public function markNotificationSent(JobMatch $match): void
    {
        $match->update(['notification_sent' => true]);
    }
} 