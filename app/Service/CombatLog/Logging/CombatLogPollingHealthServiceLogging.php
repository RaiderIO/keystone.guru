<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class CombatLogPollingHealthServiceLogging extends StructuredLogging implements CombatLogPollingHealthServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function reportSummaryHealthy(string $hour, int $dispatched, int $succeeded, int $failures, float $failureRate, array $failuresByReason): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * The one combat log polling failure signal that pages: a whole hour in which a substantial share
     * of the runs we polled for yielded nothing. Individual failures are logged below error on
     * purpose - they recover on their own, and this is the aggregate that says they didn't (#4173).
     */
    public function reportSummaryDegraded(string $hour, int $dispatched, int $succeeded, int $failures, float $failureRate, array $failuresByReason): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
