<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    protected $httpCode;
    protected $errorType;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource = null, int $httpCode = 500, string $errorType = 'server_error')
    {
        parent::__construct($resource);
        $this->httpCode = $httpCode;
        $this->errorType = $errorType;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $error = is_string($this->resource) ? ['message' => $this->resource] : $this->resource;

        return [
            'status' => 'error',
            'error' => [
                'type' => $this->errorType,
                'code' => $this->httpCode,
                'message' => $error['message'] ?? $this->getDefaultMessage(),
                'details' => $this->when(
                    isset($error['details']),
                    fn() => $error['details']
                ),
                'validation_errors' => $this->when(
                    $this->httpCode === 422 && isset($error['errors']),
                    fn() => $error['errors']
                ),
                'trace_id' => $this->generateTraceId(),
            ],
            'suggestions' => $this->getSuggestions(),
        ];
    }

    /**
     * Get default error message based on HTTP code
     */
    private function getDefaultMessage(): string
    {
        return match($this->httpCode) {
            400 => 'Bad request. Please check your input.',
            401 => 'Authentication required. Please log in.',
            403 => 'Access denied. You do not have permission for this action.',
            404 => 'The requested resource was not found.',
            409 => 'Conflict. The resource already exists or is in use.',
            422 => 'Validation failed. Please check your input.',
            429 => 'Too many requests. Please slow down.',
            500 => 'Internal server error. Please try again later.',
            503 => 'Service temporarily unavailable. Please try again later.',
            default => 'An error occurred while processing your request.'
        };
    }

    /**
     * Get suggestions based on error type
     */
    private function getSuggestions(): array
    {
        return match($this->errorType) {
            'authentication_error' => [
                'Check your credentials and try again',
                'Make sure your account is active',
                'Contact support if the problem persists',
            ],
            'authorization_error' => [
                'Contact your administrator for access',
                'Check if your account has the required permissions',
                'Try logging out and logging back in',
            ],
            'validation_error' => [
                'Check all required fields are filled',
                'Ensure data formats are correct',
                'Review the validation errors above',
            ],
            'not_found_error' => [
                'Check the URL or ID is correct',
                'The resource may have been deleted',
                'Contact support if you believe this is an error',
            ],
            'rate_limit_error' => [
                'Wait a moment before trying again',
                'Reduce the frequency of your requests',
                'Contact support for higher rate limits',
            ],
            'server_error' => [
                'Try again in a few moments',
                'Check your internet connection',
                'Contact support if the problem continues',
            ],
            default => [
                'Try refreshing the page',
                'Check your internet connection',
                'Contact support if the issue persists',
            ]
        };
    }

    /**
     * Generate a unique trace ID for error tracking
     */
    private function generateTraceId(): string
    {
        return sprintf(
            '%s-%s-%s',
            date('Ymd'),
            substr(md5(microtime()), 0, 8),
            substr(uniqid(), -6)
        );
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
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
            ],
            'support' => [
                'contact_email' => 'hello@rokanchowdhuryonick.com',
                'status_page' => 'https://status.hiresmart-backend.rokanchowdhuryonick.com',
            ],
        ];
    }

    /**
     * Static methods for common error types
     */
    public static function unauthorized(?string $message = null): self
    {
        return new self(
            $message ?? 'Authentication required',
            401,
            'authentication_error'
        );
    }

    public static function forbidden(?string $message = null): self
    {
        return new self(
            $message ?? 'Access denied',
            403,
            'authorization_error'
        );
    }

    public static function notFound(?string $message = null): self
    {
        return new self(
            $message ?? 'Resource not found',
            404,
            'not_found_error'
        );
    }

    public static function validation(array $errors): self
    {
        return new self(
            ['message' => 'Validation failed', 'errors' => $errors],
            422,
            'validation_error'
        );
    }

    public static function rateLimit(?string $message = null): self
    {
        return new self(
            $message ?? 'Rate limit exceeded',
            429,
            'rate_limit_error'
        );
    }

    public static function serverError(?string $message = null): self
    {
        return new self(
            $message ?? 'Internal server error',
            500,
            'server_error'
        );
    }
} 