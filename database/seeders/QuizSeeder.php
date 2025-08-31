<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\User;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 't@t.com')->first();

        Quiz::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'General Knowledge',
                'teacher_id' => $teacher->id,
                'duration_minutes' => 15,
            ]
        );
    }
}
