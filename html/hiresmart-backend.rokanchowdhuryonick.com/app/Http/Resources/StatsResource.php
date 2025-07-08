<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsResource extends JsonResource
{
    protected $context;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource, string $context = 'general')
    {
        parent::__construct($resource);
        $this->context = $context;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stats = is_array($this->resource) ? $this->resource : $this->resource->toArray();

        return [
            'summary' => $this->getSummaryStats($stats),
            'breakdown' => $this->getBreakdownStats($stats),
            'trends' => $this->when(
                isset($stats['trends']),
                fn() => $this->getTrendStats($stats['trends'])
            ),
            'comparisons' => $this->when(
                isset($stats['comparisons']),
                fn() => $this->getComparisonStats($stats['comparisons'])
            ),
        ];
    }

    /**
     * Get summary statistics
     */
    private function getSummaryStats(array $stats): array
    {
        return match($this->context) {
            'user' => $this->getUserSummary($stats),
            'job' => $this->getJobSummary($stats),
            'application' => $this->getApplicationSummary($stats),
            'employer' => $this->getEmployerSummary($stats),
            'platform' => $this->getPlatformSummary($stats),
            default => $this->getGeneralSummary($stats)
        };
    }

    /**
     * Get breakdown statistics
     */
    private function getBreakdownStats(array $stats): array
    {
        $breakdown = [];

        // Status breakdowns
        if (isset($stats['by_status'])) {
            $breakdown['by_status'] = $stats['by_status'];
        }

        // Time-based breakdowns
        if (isset($stats['by_period'])) {
            $breakdown['by_period'] = $stats['by_period'];
        }

        // Category breakdowns
        if (isset($stats['by_category'])) {
            $breakdown['by_category'] = $stats['by_category'];
        }

        return $breakdown;
    }

    /**
     * Get trend statistics
     */
    private function getTrendStats(array $trends): array
    {
        return array_map(function ($trend) {
            return [
                'period' => $trend['period'],
                'value' => $trend['value'],
                'change' => $trend['change'] ?? null,
                'change_percentage' => $trend['change_percentage'] ?? null,
                'direction' => $this->getTrendDirection($trend['change'] ?? 0),
            ];
        }, $trends);
    }

    /**
     * Get comparison statistics
     */
    private function getComparisonStats(array $comparisons): array
    {
        return array_map(function ($comparison) {
            return [
                'metric' => $comparison['metric'],
                'current' => $comparison['current'],
                'previous' => $comparison['previous'],
                'change' => $comparison['current'] - $comparison['previous'],
                'change_percentage' => $this->calculatePercentageChange(
                    $comparison['previous'], 
                    $comparison['current']
                ),
                'performance' => $this->getPerformanceIndicator(
                    $comparison['previous'], 
                    $comparison['current']
                ),
            ];
        }, $comparisons);
    }

    /**
     * User statistics summary
     */
    private function getUserSummary(array $stats): array
    {
        return [
            'total_applications' => $stats['total_applications'] ?? 0,
            'active_applications' => $stats['active_applications'] ?? 0,
            'response_rate' => $stats['response_rate'] ?? 0,
            'success_rate' => $stats['success_rate'] ?? 0,
            'profile_completion' => $stats['profile_completion'] ?? 0,
        ];
    }

    /**
     * Job statistics summary
     */
    private function getJobSummary(array $stats): array
    {
        return [
            'total_views' => $stats['total_views'] ?? 0,
            'total_applications' => $stats['total_applications'] ?? 0,
            'conversion_rate' => $stats['conversion_rate'] ?? 0,
            'average_time_to_fill' => $stats['average_time_to_fill'] ?? null,
            'quality_score' => $stats['quality_score'] ?? 0,
        ];
    }

    /**
     * Application statistics summary
     */
    private function getApplicationSummary(array $stats): array
    {
        return [
            'total_applications' => $stats['total'] ?? 0,
            'pending_review' => $stats['pending'] ?? 0,
            'shortlisted' => $stats['shortlisted'] ?? 0,
            'interviewed' => $stats['interviewed'] ?? 0,
            'hired' => $stats['hired'] ?? 0,
            'rejection_rate' => $stats['rejection_rate'] ?? 0,
        ];
    }

    /**
     * Employer statistics summary
     */
    private function getEmployerSummary(array $stats): array
    {
        return [
            'total_jobs' => $stats['total_jobs'] ?? 0,
            'active_jobs' => $stats['active_jobs'] ?? 0,
            'total_applications' => $stats['total_applications'] ?? 0,
            'hired_candidates' => $stats['hired_candidates'] ?? 0,
            'average_time_to_hire' => $stats['average_time_to_hire'] ?? null,
        ];
    }

    /**
     * Platform statistics summary
     */
    private function getPlatformSummary(array $stats): array
    {
        return [
            'total_users' => $stats['total_users'] ?? 0,
            'active_jobs' => $stats['active_jobs'] ?? 0,
            'monthly_applications' => $stats['monthly_applications'] ?? 0,
            'success_rate' => $stats['success_rate'] ?? 0,
            'growth_rate' => $stats['growth_rate'] ?? 0,
        ];
    }

    /**
     * General statistics summary
     */
    private function getGeneralSummary(array $stats): array
    {
        return array_filter($stats, fn($key) => !in_array($key, [
            'trends', 'comparisons', 'by_status', 'by_period', 'by_category'
        ]), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get trend direction
     */
    private function getTrendDirection(float $change): string
    {
        if ($change > 0) return 'up';
        if ($change < 0) return 'down';
        return 'stable';
    }

    /**
     * Calculate percentage change
     */
    private function calculatePercentageChange(float $previous, float $current): float
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get performance indicator
     */
    private function getPerformanceIndicator(float $previous, float $current): string
    {
        $change = $current - $previous;
        $percentChange = $this->calculatePercentageChange($previous, $current);
        
        if ($percentChange >= 10) return 'excellent';
        if ($percentChange >= 5) return 'good';
        if ($percentChange >= -5) return 'stable';
        if ($percentChange >= -10) return 'declining';
        
        return 'poor';
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'context' => $this->context,
                'generated_at' => now()->toISOString(),
                'refresh_interval' => $this->getRefreshInterval(),
            ],
        ];
    }

    /**
     * Get recommended refresh interval based on context
     */
    private function getRefreshInterval(): string
    {
        return match($this->context) {
            'platform' => '1 hour',
            'employer', 'job' => '30 minutes',
            'user', 'application' => '15 minutes',
            default => '5 minutes'
        };
    }
} 