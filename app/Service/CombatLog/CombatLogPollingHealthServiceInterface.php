<?php

namespace App\Service\CombatLog;

use App\Service\CombatLog\Dtos\CombatLogPollingHealthSummary;
use App\Service\CombatLog\Enums\CombatLogPollingFailureReason;
use Illuminate\Support\Carbon;

/**
 * Counts what combat log polling did in an hour, so that transient upstream failures can be
 * reported on in bulk instead of one Sentry issue at a time (#4173).
 *
 * Counters are kept in the shared cache rather than in the database: they are throwaway aggregates
 * read by a single hourly command, written from both the scheduler and the queue workers, and
 * nothing needs them once that command has reported on them.
 */
interface CombatLogPollingHealthServiceInterface
{
    /**
     * Records that a run was accepted for processing. Called by combatlog:pollruns as it dispatches.
     */
    public function recordDispatched(): void;

    /**
     * Records that a dispatched run yielded combat log data.
     */
    public function recordSucceeded(): void;

    /**
     * Records that a run (or the API call that would have found one) yielded no combat log data.
     */
    public function recordFailure(CombatLogPollingFailureReason $reason): void;

    /**
     * Returns the counters for the window of hours ending in the hour the given moment falls in.
     * The window defaults to the configured one and exists because a run's outcome can land in a
     * later hour than its dispatch - see the implementation.
     */
    public function getSummary(Carbon $endHour, ?int $windowHours = null): CombatLogPollingHealthSummary;

    /**
     * Logs the given hour's summary - at error level, and only then, when the hour saw a substantial
     * share of its runs fail. Returns whether it did.
     */
    public function reportSummary(CombatLogPollingHealthSummary $summary): bool;
}
