<?php

namespace App\Service\Npc\Logging;

interface NpcEncounterMechanicServiceLoggingInterface
{
    public function buildEncounterTooFewRuns(int $dungeonId, int $encounterId, int $runCount, int $minRuns): void;

    public function buildEncounterNoGameVersion(int $dungeonId, int $encounterId): void;

    public function buildEncounterBuilt(int $dungeonId, int $encounterId, int $runCount, int $rowCount): void;
}
