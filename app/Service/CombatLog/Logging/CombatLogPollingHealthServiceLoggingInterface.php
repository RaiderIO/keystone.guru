<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogPollingHealthServiceLoggingInterface
{
    /**
     * @param array<string, int> $failuresByReason
     */
    public function reportSummaryHealthy(string $hour, int $dispatched, int $succeeded, int $failures, float $failureRate, array $failuresByReason): void;

    /**
     * @param array<string, int> $failuresByReason
     */
    public function reportSummaryDegraded(string $hour, int $dispatched, int $succeeded, int $failures, float $failureRate, array $failuresByReason): void;
}
