<?php

namespace App\Service\RaiderIO\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class RaiderIOApiServiceLogging extends StructuredLogging implements RaiderIOApiServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function getHeatmapDataStart(string $url): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataInvalidResponse(string $dungeonName, string $url, string $response): void
    {
        // @TODO temporarily disable logging of invalid responses, it's spamming the logs
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getHeatmapDataEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function searchAdvancedRunsStart(string $url): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function searchAdvancedRunsInvalidResponse(string $url, string $response): void
    {
        // A single bad response from Raider.IO - typically a Cloudflare 5xx page - is transient and
        // recovers on the next poll, so it is not worth paging over (#4173). Kept at warning so it
        // is still there in the logs, and counted by CombatLogPollingHealthServiceInterface so
        // combatlog:reportpollinghealth can report on the hour's volume at error level instead.
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function searchAdvancedRunsEnd(int $count): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function getCombatLogSegmentsForRunStart(int $runId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getCombatLogSegmentsForRunInvalidResponse(int $runId, string $url, string $response): void
    {
        // Same reasoning as searchAdvancedRunsInvalidResponse() above: individually transient and
        // recovered from on its own, reported on in bulk by combatlog:reportpollinghealth (#4173).
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getCombatLogSegmentsForRunNotYetAvailable(int $runId, string $url, string $response): void
    {
        // A run's segments simply not having been uploaded to Raider.IO yet is an expected,
        // recurring state (#3918) rather than a broken API integration - logged below error so it
        // doesn't page Sentry (the sentry log channel alerts on error level; see config/logging.php).
        $this->info(__METHOD__, get_defined_vars());
    }

    public function getCombatLogSegmentsForRunEnd(int $runId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
