<?php

namespace App\Mcp\Support;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcHealth;

/**
 * Formats an NPC for MCP tool output, with its health scaled to a key level through Npc::calculateHealthForKey() - the
 * scaling the SimulationCraft export uses.
 */
class NpcFormatter
{
    /**
     * The relations format() reads, to eager-load on the NPCs passed in.
     *
     * @var list<string>
     */
    public const array RELATIONS = ['classification', 'type', 'class', 'npcHealths'];

    /**
     * @param  array<int, string>   $affixes Affix:: keys the key runs with
     * @return array<string, mixed>
     */
    public function format(
        Npc             $npc,
        GameVersion     $gameVersion,
        ?NpcEnemyForces $npcEnemyForces,
        ?int            $keyLevel,
        array           $affixes,
    ): array {
        $npcHealth = $npc->getHealthByGameVersion($gameVersion);
        // A stored health of exactly the placeholder means its real health was never learned
        $baseHealth = $npcHealth === null || $npcHealth->health === NpcHealth::HEALTH_PLACEHOLDER ?
            null :
            (int)round($npcHealth->health * (($npcHealth->percentage ?? 100) / 100));

        return array_filter([
            'id'                   => $npc->id,
            'name'                 => __($npc->name),
            'classification'       => $npc->classification?->key,
            'type'                 => $npc->type->type,
            'class'                => $npc->class->key,
            'level'                => $npc->level,
            'aggressiveness'       => $npc->aggressiveness,
            'encounter_id'         => $npc->encounter_id,
            'enemy_forces'         => $npcEnemyForces?->enemy_forces,
            'enemy_forces_teeming' => $npcEnemyForces?->enemy_forces_teeming,
            'base_health'          => $baseHealth,
            'key_level'            => $keyLevel,
            'health_at_key_level'  => $baseHealth === null || $keyLevel === null ?
                null :
                (int)$npc->calculateHealthForKey($gameVersion, $keyLevel, $affixes),
            'dangerous'         => $npc->dangerous ?: null,
            'truesight'         => $npc->truesight ?: null,
            'bursting'          => $npc->bursting ?: null,
            'bolstering'        => $npc->bolstering ?: null,
            'sanguine'          => $npc->sanguine ?: null,
            'runs_away_in_fear' => $npc->runs_away_in_fear ?: null,
            'hyper_respawn'     => $npc->hyper_respawn ?: null,
        ], static fn($value) => $value !== null);
    }
}
