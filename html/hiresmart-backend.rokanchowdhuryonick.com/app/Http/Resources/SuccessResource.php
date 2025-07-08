<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuccessResource extends JsonResource
{
    protected $httpCode;
    protected $message;
    protected $additionalData;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource = null, string $message = 'Operation successful', int $httpCode = 200, array $additionalData = [])
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->httpCode = $httpCode;
        $this->additionalData = $additionalData;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [
            'status' => 'success',
            'message' => $this->message,
        ];

        // Add data if resource is provided
        if ($this->resource !== null) {
            $response['data'] = $this->resource;
        }

        // Add any additional data
        if (!empty($this->additionalData)) {
            $response = array_merge($response, $this->additionalData);
        }

        return $response;
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
                'timestamp' => now()->toISOString(),
                'request_id' => $request->header('X-Request-ID', 'unknown'),
                'endpoint' => $request->getPathInfo(),
                'method' => $request->getMethod(),
                'http_code' => $this->httpCode,
            ],
        ];
    }

    /**
     * Static methods for common success responses
     */
    public static function created($resource = null, string $message = 'Resource created successfully'): self
    {
        return new self($resource, $message, 201);
    }

    public static function updated($resource = null, string $message = 'Resource updated successfully'): self
    {
        return new self($resource, $message, 200);
    }

    public static function deleted(string $message = 'Resource deleted successfully'): self
    {
        return new self(null, $message, 200);
    }

    public static function noContent(string $message = 'Operation completed successfully'): self
    {
        return new self(null, $message, 204);
    }

    public static function accepted(string $message = 'Request accepted for processing'): self
    {
        return new self(null, $message, 202);
    }

    public static function withPagination($resource, string $message = 'Data retrieved successfully'): self
    {
        return new self($resource, $message, 200);
    }

    public static function withMetrics($resource, array $metrics, string $message = 'Data with metrics retrieved successfully'): self
    {
        return new self($resource, $message, 200, ['metrics' => $metrics]);
    }

    public static function withStats($resource, array $stats, string $message = 'Data with statistics retrieved successfully'): self
    {
        return new self($resource, $message, 200, ['stats' => $stats]);
    }
} 