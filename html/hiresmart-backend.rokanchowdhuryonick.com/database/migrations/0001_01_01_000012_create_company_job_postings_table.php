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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // employer
            $table->string('title');
            $table->text('description');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->foreignId('state_id')->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->foreignId('area_id')->constrained()->onDelete('cascade');
            $table->decimal('min_salary', 10, 2)->nullable();
            $table->decimal('max_salary', 10, 2)->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship']);
            $table->enum('status', ['active', 'closed', 'draft', 'archived'])->default('active');
            $table->date('deadline')->nullable();
            $table->integer('experience_years')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'country_id', 'state_id', 'city_id', 'area_id']);
            $table->index(['employment_type', 'status']);
            $table->index('deadline');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
}; 