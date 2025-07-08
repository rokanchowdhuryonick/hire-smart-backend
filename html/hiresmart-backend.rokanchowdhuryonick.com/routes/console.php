<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Archive old jobs daily at 2 AM
Schedule::command('jobs:archive-old')->daily()->at('02:00');

// Remove unverified users weekly on Sunday at 3 AM
Schedule::command('users:remove-unverified')->weekly()->sundays()->at('03:00');

// Run job matching every hour
Schedule::job(new \App\Jobs\JobMatchingJob)->hourly();
