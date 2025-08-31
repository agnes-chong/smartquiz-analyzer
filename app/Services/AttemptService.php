<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Schema;

class AttemptService
{
    public function __construct(private ScoringStrategyResolver $resolver) {}

    /**
     * Grade a quiz using Strategy pattern.
     * $answers shape: [question_id => userInput]
     */
    public function grade(Quiz $quiz, array $answers, int $userId): QuizAttempt
    {
        $quiz->loadMissing('questions');

        $total  = 0.0;
        $detail = [];

        foreach ($quiz->questions as $q) {
            // 🧹 (optional) normalize legacy types if needed
            $type = strtolower($q->type);
            if ($type === 'multiple_choice') $type = 'mcq';
            if ($type === 'short_answer')    $type = 'short';

            // ✅ Skip unanswered questions for a cleaner detail JSON
            if (!array_key_exists($q->id, $answers)) {
                continue;
            }

            $given = $answers[$q->id];
            $score = $this->resolver->for($q)->score($q, $given);

            $detail[$q->id] = [
                'given'  => $given,
                'score'  => $score,
                'marks'  => (float)($q->marks ?? 1),
                'policy' => $q->scoring ?? 'exact',
            ];

            $total += $score;
        }

        // (optional) percent for UI/report
        $maxMarks = $quiz->questions->sum(fn($qq) => (float)($qq->marks ?? 1));
        $percent  = $maxMarks > 0 ? round(($total / $maxMarks) * 100, 2) : 0.0;

        $payload = [
            'quiz_id' => $quiz->id,
            'score'   => round($total, 2),
            'detail'  => $detail,
            // 'percent' => $percent, // add if you created this column
        ];

        // Save to whichever FK exists
        if (Schema::hasColumn('quiz_attempts', 'student_id')) {
            $payload['student_id'] = $userId;
        } elseif (Schema::hasColumn('quiz_attempts', 'user_id')) {
            $payload['user_id'] = $userId;
        }

        return QuizAttempt::create($payload);
    }
}
