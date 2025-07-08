<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApplicationCollection extends ResourceCollection
{
    protected $appliedFilters;
    protected $userRole;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource, array $appliedFilters = [], string $userRole = 'candidate')
    {
        parent::__construct($resource);
        $this->appliedFilters = $appliedFilters;
        $this->userRole = $userRole;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => ApplicationResource::collection($this->collection),
            'pagination' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
                'has_more_pages' => $this->hasMorePages(),
                'path' => $this->path(),
                'links' => [
                    'first' => $this->url(1),
                    'last' => $this->url($this->lastPage()),
                    'prev' => $this->previousPageUrl(),
                    'next' => $this->nextPageUrl(),
                ],
            ],
            'filters' => [
                'applied' => array_filter($this->appliedFilters),
                'available_filters' => $this->getAvailableFilters(),
            ],
            'summary' => [
                'total_applications' => $this->total(),
                'status_breakdown' => $this->getStatusBreakdown(),
                'recent_activity' => $this->getRecentActivity(),
            ],
            'bulk_actions' => $this->when(
                $this->userRole === 'employer',
                fn() => $this->getBulkActions()
            ),
        ];
    }

    /**
     * Get available filter options
     */
    private function getAvailableFilters(): array
    {
        return [
            'status' => [
                'pending' => 'Under Review',
                'shortlisted' => 'Shortlisted',
                'interviewed' => 'Interviewed',
                'hired' => 'Hired',
                'rejected' => 'Not Selected',
                'withdrawn' => 'Withdrawn',
            ],
            'date_range' => [
                'today' => 'Today',
                'this_week' => 'This Week',
                'this_month' => 'This Month',
                'last_30_days' => 'Last 30 Days',
                'last_90_days' => 'Last 90 Days',
            ],
            'sort_options' => [
                'applied_at_desc' => 'Newest First',
                'applied_at_asc' => 'Oldest First',
                'status' => 'By Status',
                'job_title' => 'By Job Title',
            ],
        ];
    }

    /**
     * Get status breakdown for current collection
     */
    private function getStatusBreakdown(): array
    {
        return $this->collection
            ->groupBy('status')
            ->map(fn($apps) => $apps->count())
            ->toArray();
    }

    /**
     * Get recent activity summary
     */
    private function getRecentActivity(): array
    {
        $recentApps = $this->collection->filter(
            fn($app) => $app->applied_at->isAfter(now()->subDays(7))
        );

        $recentReviews = $this->collection->filter(
            fn($app) => $app->reviewed_at && $app->reviewed_at->isAfter(now()->subDays(7))
        );

        return [
            'applications_this_week' => $recentApps->count(),
            'reviews_this_week' => $recentReviews->count(),
            'pending_review' => $this->collection->where('status', 'pending')->count(),
            'needs_attention' => $this->collection->filter(
                fn($app) => $app->status === 'pending' && $app->applied_at->isBefore(now()->subDays(3))
            )->count(),
        ];
    }

    /**
     * Get available bulk actions for employers
     */
    private function getBulkActions(): array
    {
        return [
            'update_status' => [
                'label' => 'Update Status',
                'options' => [
                    'shortlisted' => 'Move to Shortlisted',
                    'interviewed' => 'Mark as Interviewed',
                    'rejected' => 'Reject Applications',
                ],
            ],
            'export' => [
                'label' => 'Export',
                'formats' => ['csv', 'excel', 'pdf'],
            ],
            'send_message' => [
                'label' => 'Send Message',
                'templates' => [
                    'interview_invitation' => 'Interview Invitation',
                    'status_update' => 'Status Update',
                    'rejection_notice' => 'Rejection Notice',
                ],
            ],
        ];
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
                'total_applications' => $this->total(),
                'showing' => sprintf(
                    'Showing %d to %d of %d applications',
                    $this->firstItem() ?? 0,
                    $this->lastItem() ?? 0,
                    $this->total()
                ),
                'filters_applied' => count(array_filter($this->appliedFilters)),
                'user_role' => $this->userRole,
                'generated_at' => now()->toISOString(),
            ],
        ];
    }
} 