<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use App\Service\CombatLog\Dtos\DataExtraction\NpcHealthChange;
use App\Service\CombatLog\Dtos\DataExtraction\NpcHealthObservation;
use Illuminate\Support\Collection;

/**
 * Turns the max HP values NpcHealthDataExtractor collected from a combat log into base health for the mapping.
 *
 * The forward formula is Npc::calculateHealthForKey(): health * (percentage ?? 100) / 100 * getScalingFactor().
 * The reversal therefore has to honour the row's percentage as well as the scaling factor - both attempts that
 * preceded this one (CreateMissingNpcDataExtractor's commented-out base health, and the since-deleted
 * NpcUpdateDataExtractor) divided by the factor alone.
 */
class NpcHealthExtractionService implements NpcHealthExtractionServiceInterface
{
    public function compareNpcHealths(Collection $observations, Dungeon $dungeon, GameVersion $gameVersion): Collection
    {
        /** @var Collection<int, Npc> $npcs */
        $npcs = $dungeon->npcs()->with('npcHealths')->get()->keyBy('id');

        /** @var Collection<int, NpcHealthChange> $result */
        $result = collect();

        // The same NPC may have been observed at several key levels when the files span multiple runs - the lowest
        // key level stacks the fewest multipliers on top of the base health, so it is the most trustworthy reversal
        $observationsByNpc = $observations
            ->sortBy(static fn(NpcHealthObservation $observation) => $observation->keyLevel)
            ->groupBy(static fn(NpcHealthObservation $observation) => $observation->npcId);

        foreach ($observationsByNpc as $npcId => $npcObservations) {
            /** @var Npc|null $npc */
            $npc = $npcs->get($npcId);
            if ($npc === null) {
                // Not an NPC of this dungeon - player-summoned creatures, or something we never mapped
                continue;
            }

            /** @var NpcHealthObservation $observation */
            $observation = $npcObservations->first();

            $scalingFactor      = $npc->getScalingFactor($observation->keyLevel, $observation->affixes);
            $observedBaseHealth = (int)round($observation->getMostObservedMaxHp() / $scalingFactor);

            $existingNpcHealth = $npc->getHealthByGameVersion($gameVersion);
            $percentage        = $existingNpcHealth?->percentage;

            // Keep the row's percentage; store whatever `health` makes health * percentage / 100 equal the observed base
            $newHealth = $percentage === null || $percentage <= 0 ?
                $observedBaseHealth :
                (int)round($observedBaseHealth * 100 / $percentage);

            $result->put($npc->id, new NpcHealthChange(
                $npc,
                $existingNpcHealth,
                $observation,
                $scalingFactor,
                $observedBaseHealth,
                $newHealth,
            ));
        }

        return $result;
    }

    public function applyNpcHealths(Collection $changes, GameVersion $gameVersion, bool $overwrite = false): int
    {
        $written = 0;

        foreach ($changes as $change) {
            if (!$change->shouldWrite($overwrite)) {
                continue;
            }

            if ($change->existingNpcHealth === null) {
                NpcHealth::create([
                    'npc_id'          => $change->npc->id,
                    'game_version_id' => $gameVersion->id,
                    'health'          => $change->newHealth,
                    'percentage'      => null,
                ]);
            } else {
                $change->existingNpcHealth->update([
                    'health' => $change->newHealth,
                ]);
            }

            $written++;
        }

        return $written;
    }
}
