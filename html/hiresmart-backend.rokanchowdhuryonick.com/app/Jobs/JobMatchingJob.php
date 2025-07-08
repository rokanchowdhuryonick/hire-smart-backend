<?php

namespace App\Jobs;

use App\Models\JobPosting;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class JobMatchingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MatchingService $matchingService)
    {
        // Use the comprehensive matching service that handles everything
        $result = $matchingService->runJobMatching();
        
        Log::info("Job matching job completed", $result);
    }
} 