<?php
namespace App\Strategies\Scoring;
use App\Contracts\ScoringStrategy;
use App\Models\Question;

class PartialCreditStrategy implements ScoringStrategy {
    public function score(Question $q, mixed $given): float {
        $correct = (array) $q->answer;              // e.g. ["A","C"]
        $chosen  = array_unique((array) $given);    // e.g. ["A","B","C"]
        if (!$correct) return 0.0;
        $hits = count(array_intersect($correct, $chosen));
        $miss = max(count($chosen) - $hits, 0);     // wrong picks
        $ratio = max(($hits/max(count($correct),1)) - 0.25*$miss, 0.0); // tune penalty
        return round($ratio * (float)$q->marks, 2);
    }
}
