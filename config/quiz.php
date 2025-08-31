<?php
return [
  'scoring_map' => [
    'exact'    => \App\Strategies\Scoring\ExactMatchStrategy::class,
    'partial'  => \App\Strategies\Scoring\PartialCreditStrategy::class,
    'negative' => \App\Strategies\Scoring\NegativeMarkingStrategy::class,
  ],
];
