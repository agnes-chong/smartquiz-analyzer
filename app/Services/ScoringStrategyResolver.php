<?php
namespace App\Services;
use App\Contracts\ScoringStrategy;
use App\Models\Question;
use Illuminate\Contracts\Container\Container;

class ScoringStrategyResolver {
    public function __construct(private Container $app) {}
    public function for(Question $q): ScoringStrategy {
        $map = config('quiz.scoring_map');
        $class = $map[$q->scoring] ?? \App\Strategies\Scoring\ExactMatchStrategy::class;
        return $this->app->make($class);
    }
}
