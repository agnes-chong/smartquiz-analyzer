<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Quiz;
use App\Models\QuizAttempt;

class AttemptController extends Controller
{
    public function create(Quiz $quiz)
    {
        $quiz->load(['questions' => fn($q) => $q->inRandomOrder()]);
        return view('attempts.create', compact('quiz'));
    }

    public function store(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'answers'   => 'required|array',
            'answers.*' => 'nullable|string',
        ]);

        $attempt = null;

        DB::transaction(function () use ($quiz, $data, &$attempt) {
            $quiz->load('questions');

            $attempt = QuizAttempt::create([
                'quiz_id'    => $quiz->id,
                'student_id' => auth()->id(),
                'started_at' => now(),
                'completed_at' => now(),
                'score'      => 0,
            ]);

            $score = 0;

            foreach ($quiz->questions as $question) {
                $studentAnswer = $data['answers'][$question->id] ?? null;
                $isCorrect = $studentAnswer == $question->correct_answer;
                if ($isCorrect) $score++;

                $attempt->answers()->create([
                    'question_id'    => $question->id,
                    'student_answer' => $studentAnswer,
                    'is_correct'     => $isCorrect,
                ]);
            }

            $attempt->update(['score' => $score]);
        });

        return redirect()->route('attempts.show', $attempt);
    }

    public function show(QuizAttempt $attempt)
    {
        $attempt->load(['quiz', 'answers.question']);
        return view('attempts.show', compact('attempt'));
    }
}
