<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\User;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        // ensure teacher exists
        $teacher = User::firstOrCreate(
            ['email' => 't@t.com'],
            ['name' => 'Teacher', 'password' => bcrypt('pass'), 'role' => 'teacher']
        );

        Quiz::firstOrCreate(
            ['id' => 1],
            [
                'title'            => 'General Knowledge',
                'teacher_id'       => $teacher->id,
                'duration_minutes' => 15,
                'is_active'        => true,
                'starts_at'        => now()->subDay(),
                'ends_at'          => now()->addMonth(),
            ]
        );
    }
}
