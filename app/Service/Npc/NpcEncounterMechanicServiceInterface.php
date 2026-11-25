<?php

namespace App\Service\Npc;

use App\Models\Dungeon;
use App\Service\Npc\Dtos\NpcEncounterMechanicBuildResult;
use Illuminate\Support\Collection;

interface NpcEncounterMechanicServiceInterface
{
    /**
     * Rebuilds the mechanics of every observed encounter, or of the dungeon's encounters only.
     *
     * @return Collection<int, NpcEncounterMechanicBuildResult>
     */
    public function buildAll(?Dungeon $dungeon = null): Collection;

    /**
     * Replaces the encounter's npc_encounter_mechanics rows with what recurs across its most recent kills. An encounter
     * with fewer kills than keystoneguru.combat_log_encounter_observations.mechanics.min_runs keeps its rows.
     */
    public function buildEncounter(int $dungeonId, int $encounterId): NpcEncounterMechanicBuildResult;
}
