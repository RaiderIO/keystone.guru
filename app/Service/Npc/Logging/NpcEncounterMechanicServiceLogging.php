<?php

namespace App\Service\Npc\Logging;

use App\Logging\StructuredLogging;

class NpcEncounterMechanicServiceLogging extends StructuredLogging implements NpcEncounterMechanicServiceLoggingInterface
{
    public function buildEncounterTooFewRuns(int $dungeonId, int $encounterId, int $runCount, int $minRuns): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function buildEncounterNoGameVersion(int $dungeonId, int $encounterId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function buildEncounterBuilt(int $dungeonId, int $encounterId, int $runCount, int $rowCount): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
