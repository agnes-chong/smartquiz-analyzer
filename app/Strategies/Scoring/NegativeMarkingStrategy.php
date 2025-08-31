<?php
namespace App\Strategies\Scoring;
use App\Contracts\ScoringStrategy;
use App\Models\Question;

class NegativeMarkingStrategy implements ScoringStrategy {
    public function score(Question $q, mixed $given): float {
        if ((string)$given === (string)$q->answer) return (float)$q->marks;
        $penalty = (float)($q->penalty ?? 0.25 * $q->marks);
        return max(0.0, (float)$q->marks - $penalty);
    }
}
