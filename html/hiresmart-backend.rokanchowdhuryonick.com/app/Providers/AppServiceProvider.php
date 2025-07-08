<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register service layer bindings
        $this->app->bind(\App\Services\AuthService::class);
        $this->app->bind(\App\Services\UserService::class);
        $this->app->bind(\App\Services\JobService::class);
        $this->app->bind(\App\Services\ApplicationService::class);
        $this->app->bind(\App\Services\MatchingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
