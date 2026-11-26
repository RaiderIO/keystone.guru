<?php

namespace App\Service\Compendium\Dtos;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcEncounterMechanic;
use Illuminate\Support\Collection;

/**
 * What the NPC Compendium shows about an NPC's boss encounters, with every NPC the lines name loaded.
 */
readonly class NpcEncounterMechanics
{
    /**
     * @param Collection<int, NpcEncounterMechanic> $encounterMechanics    the mechanics of the encounters the NPC is a boss of
     * @param Collection<int, NpcEncounterMechanic> $npcMechanics          the rows about the NPC in encounters it is no boss of
     * @param Collection<int, Npc>                  $npcsById              every NPC a line names
     * @param array<string, Npc>                    $bossNpcByEncounterKey {@see self::getEncounterKey()} => the encounter's boss with the lowest id
     */
    public function __construct(
        public Collection $encounterMechanics,
        public Collection $npcMechanics,
        public Collection $npcsById,
        public array      $bossNpcByEncounterKey,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->encounterMechanics->isEmpty() && $this->npcMechanics->isEmpty();
    }

    public function getBossNpc(NpcEncounterMechanic $npcEncounterMechanic): ?Npc
    {
        return $this->bossNpcByEncounterKey[self::getEncounterKey($npcEncounterMechanic)] ?? null;
    }

    public static function getEncounterKey(NpcEncounterMechanic $npcEncounterMechanic): string
    {
        return sprintf('%d:%d', $npcEncounterMechanic->dungeon_id, $npcEncounterMechanic->encounter_id);
    }
}
