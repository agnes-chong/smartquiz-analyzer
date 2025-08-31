<?php
//Author: Chong Pei Lee
namespace App\Contracts;
use App\Models\Question;

interface ScoringStrategy {
    public function score(Question $question, mixed $given): float;
}
