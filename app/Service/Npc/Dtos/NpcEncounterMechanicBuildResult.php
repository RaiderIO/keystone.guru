<?php

namespace App\Service\Npc\Dtos;

readonly class NpcEncounterMechanicBuildResult
{
    /**
     * @param array<string, int>|null $rowCountsByType {@see \App\Models\Npc\NpcEncounterMechanicType} value => rows written, or null
     *                                                 when the encounter had too few kills and kept its rows
     */
    public function __construct(
        public int    $dungeonId,
        public int    $encounterId,
        public int    $runCount,
        public ?array $rowCountsByType,
    ) {
    }
}
