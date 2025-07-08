<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed master data and admin user
        $this->call([
            SkillSeeder::class,        // Seed skills first (referenced by users/jobs)
            LocationSeeder::class,     // Seed locations
            AdminSeeder::class,        // Create admin user
        ]);
    }
}
