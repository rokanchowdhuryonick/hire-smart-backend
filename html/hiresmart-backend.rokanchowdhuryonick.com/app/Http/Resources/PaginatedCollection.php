<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaginatedCollection extends ResourceCollection
{
    protected $resourceClass;
    protected $metaData;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource, string $resourceClass = null, array $metaData = [])
    {
        parent::__construct($resource);
        $this->resourceClass = $resourceClass;
        $this->metaData = $metaData;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resourceClass 
            ? $this->resourceClass::collection($this->collection)
            : $this->collection;

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
                'has_more_pages' => $this->hasMorePages(),
                'links' => [
                    'first' => $this->url(1),
                    'last' => $this->url($this->lastPage()),
                    'prev' => $this->previousPageUrl(),
                    'next' => $this->nextPageUrl(),
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
        $baseWith = [
            'meta' => array_merge([
                'total_items' => $this->total(),
                'items_per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'showing' => sprintf(
                    'Showing %d to %d of %d results',
                    $this->firstItem() ?? 0,
                    $this->lastItem() ?? 0,
                    $this->total()
                ),
                'generated_at' => now()->toISOString(),
            ], $this->metaData),
        ];

        return $baseWith;
    }

    /**
     * Create a paginated collection with custom resource class
     */
    public static function paginated($resource, string $resourceClass, array $metaData = []): self
    {
        return new self($resource, $resourceClass, $metaData);
    }
} 