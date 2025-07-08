<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    protected $token;
    protected $tokenType;
    protected $expiresIn;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource, string $token, string $tokenType = 'bearer', int $expiresIn = 3600)
    {
        parent::__construct($resource);
        $this->token = $token;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
            'authorization' => [
                'token' => $this->token,
                'type' => $this->tokenType,
                'expires_in' => $this->expiresIn,
                'expires_at' => now()->addSeconds($this->expiresIn)->toISOString(),
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
                'login_time' => now()->toISOString(),
                'token_type' => $this->tokenType,
                'remember_token_header' => 'Authorization: Bearer ' . $this->token,
            ],
        ];
    }
} 