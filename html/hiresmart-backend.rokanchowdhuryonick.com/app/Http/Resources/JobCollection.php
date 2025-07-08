<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class JobCollection extends ResourceCollection
{
    protected $appliedFilters;
    protected $searchQuery;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource, array $appliedFilters = [], ?string $searchQuery = null)
    {
        parent::__construct($resource);
        $this->appliedFilters = $appliedFilters;
        $this->searchQuery = $searchQuery;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => JobResource::collection($this->collection),
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
                'search_query' => $this->searchQuery,
                'available_filters' => $this->getAvailableFilters(),
            ],
            'suggestions' => $this->when(
                empty($this->collection) && !empty($this->searchQuery),
                fn() => $this->getSearchSuggestions()
            ),
        ];
    }

    /**
     * Get available filter options
     */
    private function getAvailableFilters(): array
    {
        return [
            'employment_type' => [
                'full_time' => 'Full Time',
                'part_time' => 'Part Time',
                'contract' => 'Contract',
                'internship' => 'Internship',
            ],
            'salary' => [
                'type' => 'range',
                'min_field' => 'min_salary',
                'max_field' => 'max_salary',
                'description' => 'Salary range filter (use min_salary and max_salary parameters)'
            ],
            'location' => [
                'type' => 'hierarchical',
                'fields' => ['country_id', 'state_id', 'city_id', 'area_id'],
                'description' => 'Location hierarchy filter (most specific location takes precedence)'
            ],
            'other_filters' => [
                'search' => 'Search in job title and description',
                'skills' => 'Filter by required skills (comma-separated skill IDs)',
                'company_id' => 'Filter by specific company',
                'experience_years' => 'Filter by required years of experience (integer)',
            ],
            'sorting' => [
                'sort_by' => ['created_at', 'min_salary', 'max_salary', 'deadline'],
                'sort_order' => ['desc', 'asc'],
                'default' => ['sort_by' => 'created_at', 'sort_order' => 'desc']
            ],
        ];
    }

    /**
     * Get search suggestions when no results found
     */
    private function getSearchSuggestions(): array
    {
        return [
            'try_different_keywords' => 'Try different or more general keywords',
            'check_spelling' => 'Check your spelling',
            'broaden_search' => 'Remove some filters to broaden your search',
            'popular_searches' => [
                'Software Developer',
                'Marketing Manager',
                'Data Analyst',
                'Project Manager',
                'Sales Representative',
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
                'total_jobs' => $this->total(),
                'showing' => sprintf(
                    'Showing %d to %d of %d jobs',
                    $this->firstItem() ?? 0,
                    $this->lastItem() ?? 0,
                    $this->total()
                ),
                'filters_applied' => count(array_filter($this->appliedFilters)),
                'search_performed' => !empty($this->searchQuery),
                'generated_at' => now()->toISOString(),
            ],
        ];
    }
} 