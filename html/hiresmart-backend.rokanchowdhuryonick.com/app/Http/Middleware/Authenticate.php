<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API routes, return null to prevent redirects and trigger JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        return route('login');
    }

    /**
     * Handle unauthenticated user for API requests
     */
    protected function unauthenticated($request, array $guards)
    {
        // For API requests, return JSON error response
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401));
        }

        // For web requests, use default behavior
        parent::unauthenticated($request, $guards);
    }
} 