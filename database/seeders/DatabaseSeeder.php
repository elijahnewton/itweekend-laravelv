<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@lms.local'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Sample student
        User::updateOrCreate(
            ['email' => 'student@lms.local'],
            [
                'name' => 'Sample Student',
                'password' => Hash::make('student123'),
                'role' => 'student',
            ]
        );

        // Run content sync
        $this->call(ContentSeeder::class);
    }
}

