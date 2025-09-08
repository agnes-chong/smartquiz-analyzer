<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Q1: MCQ single-correct (Paris)
        Question::updateOrCreate(
            ['id' => 1],
            [
                'quiz_id'  => 1,
                'type'     => 'mcq',
                'text'     => 'Name the capital of France.',
                'marks'    => 2,
                'scoring'  => 'exact',
                'penalty'  => null,
                'data'     => ['prompt' => 'Name the capital of France.', 'options' => ['Paris','Lyon','Marseille']],
                'answer'   => [0],                // index of correct option
                'correct_answer' => '0',          // optional mirror
            ]
        );

        // Q2: True/False (False is correct)
        Question::updateOrCreate(
            ['id' => 2],
            [
                'quiz_id'  => 1,
                'type'     => 'tf',
                'text'     => 'Sun rises from the West?',
                'marks'    => 1,
                'scoring'  => 'exact',
                'penalty'  => null,
                'data'     => ['prompt' => 'Sun rises from the West?', 'options' => ['True','False']],
                'answer'   => [1],                // 1 => "False" (since options are ["True","False"])
                'correct_answer' => '1',
            ]
        );

        // Q3: Short (manual)
        Question::updateOrCreate(
            ['id' => 3],
            [
                'quiz_id'  => 1,
                'type'     => 'short',
                'text'     => 'Briefly describe why Paris is famous.',
                'marks'    => 4,
                'scoring'  => 'manual',           // manual grading
                'penalty'  => null,
                'data'     => ['prompt' => 'Briefly describe why Paris is famous.', 'options' => null],
                'answer'   => ['paris'],          // canonical list (unused for manual, ok to keep)
                'correct_answer' => 'paris',
            ]
        );
    }
}
