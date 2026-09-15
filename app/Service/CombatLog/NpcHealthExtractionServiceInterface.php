<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\CombatLog\Dtos\DataExtraction\NpcHealthChange;
use App\Service\CombatLog\Dtos\DataExtraction\NpcHealthObservation;
use Illuminate\Support\Collection;

interface NpcHealthExtractionServiceInterface
{
    /**
     * Reverses the key-level scaling on every observed NPC of $dungeon and lines the result up against the
     * npc_healths row it has for $gameVersion. Observations of NPCs that are not part of $dungeon are ignored.
     *
     * @param Collection<string, NpcHealthObservation> $observations
     *
     * @return Collection<int, NpcHealthChange> keyed by npc id
     */
    public function compareNpcHealths(Collection $observations, Dungeon $dungeon, GameVersion $gameVersion): Collection;

    /**
     * Writes the changes that {@see NpcHealthChange::shouldWrite()} allows.
     *
     * @param Collection<int, NpcHealthChange> $changes
     *
     * @return int The number of npc_healths rows created or updated
     */
    public function applyNpcHealths(Collection $changes, GameVersion $gameVersion, bool $overwrite = false): int;
}
