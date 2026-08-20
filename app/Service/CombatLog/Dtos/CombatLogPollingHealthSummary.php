<?php

namespace App\Service\CombatLog\Dtos;

use App\Service\CombatLog\Enums\CombatLogPollingFailureReason;

/**
 * What one hour of combat log polling amounted to: how many runs were dispatched, how many of them
 * yielded data, and how many failed for each reason.
 */
readonly class CombatLogPollingHealthSummary
{
    /**
     * @param array<string, int> $failuresByReason Keyed by CombatLogPollingFailureReason value, every reason present.
     */
    public function __construct(
        public string $hour,
        public int    $dispatched,
        public int    $succeeded,
        public array  $failuresByReason,
    ) {
    }

    public function getTotalFailures(): int
    {
        return array_sum($this->failuresByReason);
    }

    /**
     * Failures as a fraction of everything that was attempted this hour.
     *
     * Failures are counted against the number of dispatched runs where that is the larger of the
     * two: the failure counters include ones that never belonged to a dispatched run at all (a
     * search API error happens before any run is picked), and a run dispatched at the end of an
     * hour fails in the next one. Neither should be able to produce a rate above 1.
     */
    public function getFailureRate(): float
    {
        $totalFailures = $this->getTotalFailures();
        $denominator   = max($this->dispatched, $totalFailures);

        return $denominator === 0 ? 0.0 : $totalFailures / $denominator;
    }

    public function getFailureCount(CombatLogPollingFailureReason $reason): int
    {
        return $this->failuresByReason[$reason->value] ?? 0;
    }

    public function isEmpty(): bool
    {
        return $this->dispatched === 0 && $this->succeeded === 0 && $this->getTotalFailures() === 0;
    }
}
