<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveUnverifiedUsers extends Command
{
    protected $signature = 'users:remove-unverified';
    protected $description = 'Remove unverified users older than 7 days';

    public function handle()
    {
        $deletedCount = 0;

        // Process in chunks of 100 to avoid memory/timeout issues
        User::where('email_verified_at', null)
            ->where('is_active', false)
            ->where('created_at', '<', now()->subDays(7))
            ->chunkById(100, function ($users) use (&$deletedCount) {
                foreach ($users as $user) {
                    $user->delete();
                    $deletedCount++;
                }
                $this->info("Processed chunk, deleted: {$deletedCount} users so far...");
            });

        Log::info("Deleted {$deletedCount} unverified users");
        $this->info("✅ Completed: Deleted {$deletedCount} unverified users");
    }
} 