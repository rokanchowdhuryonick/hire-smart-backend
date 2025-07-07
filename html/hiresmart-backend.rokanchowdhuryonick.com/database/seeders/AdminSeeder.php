<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the first admin user
        User::create([
            'name' => 'HireSmart Admin',
            'email' => 'hello+admin@rokanbd.cf',
            'password' => Hash::make('123456789'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: hello+admin@rokanbd.cf');
        $this->command->info('Password: 123456789');
    }
} 