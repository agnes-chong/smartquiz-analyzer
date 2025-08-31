<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Answer;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Q1: MCQ
        $q1 = Question::updateOrCreate(
            ['id' => 1],
            [
                'quiz_id' => 1,
                'text' => 'Name the capital of France.',
                'type' => 'mcq',
            ]
        );
        Answer::updateOrCreate(['id' => 1], ['question_id' => $q1->id, 'text' => 'Paris', 'is_correct' => true]);
        Answer::updateOrCreate(['id' => 2], ['question_id' => $q1->id, 'text' => 'Lyon', 'is_correct' => false]);
        Answer::updateOrCreate(['id' => 3], ['question_id' => $q1->id, 'text' => 'Marseille', 'is_correct' => false]);

        // Q2: True/False
        $q2 = Question::updateOrCreate(
            ['id' => 2],
            [
                'quiz_id' => 1,
                'text' => 'Sun rises from the West?',
                'type' => 'tf',
            ]
        );
        Answer::updateOrCreate(['id' => 4], ['question_id' => $q2->id, 'text' => 'True', 'is_correct' => false]);
        Answer::updateOrCreate(['id' => 5], ['question_id' => $q2->id, 'text' => 'False', 'is_correct' => true]);

        // Q3: Short
        $q3 = Question::updateOrCreate(
            ['id' => 3],
            [
                'quiz_id' => 1,
                'text' => 'Briefly describe why Paris is famous.',
                'type' => 'short',
            ]
        );
        // No options for short
    }
}
