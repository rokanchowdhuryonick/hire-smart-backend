<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimit
{
    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }
} 