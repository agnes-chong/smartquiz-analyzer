<?php
namespace App\Strategies\Scoring;
use App\Contracts\ScoringStrategy;
use App\Models\Question;

class ExactMatchStrategy implements ScoringStrategy {
    public function score(Question $q, mixed $given): float {
        if ($given === null) return 0.0;
        $correct = $q->answer;                 // string|array depending on your schema
        if (is_array($correct)) {
            $g = (array) $given; sort($g); sort($correct);
            return $g === $correct ? (float)$q->marks : 0.0;
        }
        return (string)$given === (string)$correct ? (float)$q->marks : 0.0;
    }
}
