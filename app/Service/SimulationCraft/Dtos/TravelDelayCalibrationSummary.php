<?php

namespace App\Service\SimulationCraft\Dtos;

/**
 * How the predicted travel delays of a set of transitions compare with the observed ones. Only included transitions
 * count towards the statistics.
 */
readonly class TravelDelayCalibrationSummary
{
    /**
     * @param int        $includedCount          Transitions the statistics are based on.
     * @param int        $excludedCount          Transitions left out (chain pulls, player deaths).
     * @param float      $medianPredictedSeconds Median predicted delay; 0 when nothing was included.
     * @param float      $medianObservedSeconds  Median observed delay; 0 when nothing was included.
     * @param float      $totalPredictedSeconds  Sum of all predicted delays.
     * @param float      $totalObservedSeconds   Sum of all observed delays.
     * @param float|null $medianRatio            Median of observed / predicted over the transitions with a predicted delay above 0.
     * @param float|null $slope                  Least-squares fit observed = slope * predicted + intercept; null without spread in the predictions.
     * @param float|null $intercept              The fixed overhead per transition of that fit.
     */
    public function __construct(
        public int    $includedCount,
        public int    $excludedCount,
        public float  $medianPredictedSeconds,
        public float  $medianObservedSeconds,
        public float  $totalPredictedSeconds,
        public float  $totalObservedSeconds,
        public ?float $medianRatio,
        public ?float $slope,
        public ?float $intercept,
    ) {
    }
}
