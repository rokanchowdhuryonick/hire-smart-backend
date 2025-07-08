<?php

namespace App\Console\Commands;

use App\Models\JobPosting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ArchiveOldJobs extends Command
{
    protected $signature = 'jobs:archive-old';
    protected $description = 'Archive job posts older than 30 days';

    public function handle()
    {
        $archivedCount = 0;

        // Process in chunks of 50 to avoid database locks
        JobPosting::where('status', 'active')
            ->where('created_at', '<', now()->subDays(30))
            ->chunkById(50, function ($jobs) use (&$archivedCount) {
                foreach ($jobs as $job) {
                    $job->update([
                        'status' => 'archived',
                        'archived_at' => now()
                    ]);
                    $archivedCount++;
                }
                $this->info("Processed chunk, archived: {$archivedCount} jobs so far...");
            });

        Log::info("Archived {$archivedCount} old jobs");
        $this->info("✅ Completed: Archived {$archivedCount} old jobs");
    }
} 