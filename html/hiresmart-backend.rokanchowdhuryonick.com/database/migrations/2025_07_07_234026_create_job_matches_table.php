<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade'); // candidate user
            $table->decimal('match_score', 5, 4); // 0.0000 to 1.0000 (100%)
            $table->json('match_reasons'); // {'skills': 0.8, 'location': 1.0, 'salary': 0.9}
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('created_at')->useCurrent();
            
            $table->unique(['job_posting_id', 'candidate_id']); // Prevent duplicate matches
            $table->index(['candidate_id', 'match_score']);
            $table->index(['job_posting_id', 'match_score']);
            $table->index('notification_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_matches');
    }
};
