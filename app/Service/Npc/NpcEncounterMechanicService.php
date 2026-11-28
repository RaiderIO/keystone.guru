<?php

namespace App\Service\Npc;

use App\Logic\EncounterMechanics\EncounterMechanicAggregator;
use App\Models\CombatLog\CombatLogEncounterObservation;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Repositories\Interfaces\CombatLog\CombatLogEncounterObservationRepositoryInterface;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcEncounterMechanicRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\Npc\Dtos\NpcEncounterMechanicBuildResult;
use App\Service\Npc\Logging\NpcEncounterMechanicServiceLoggingInterface;
use Illuminate\Support\Collection;

class NpcEncounterMechanicService implements NpcEncounterMechanicServiceInterface
{
    public function __construct(
        private readonly CombatLogEncounterObservationRepositoryInterface $combatLogEncounterObservationRepository,
        private readonly NpcEncounterMechanicRepositoryInterface          $npcEncounterMechanicRepository,
        private readonly NpcRepositoryInterface                           $npcRepository,
        private readonly DungeonRepositoryInterface                       $dungeonRepository,
        private readonly NpcEncounterMechanicServiceLoggingInterface      $log,
    ) {
    }

    public function buildAll(?Dungeon $dungeon = null): Collection
    {
        return $this->combatLogEncounterObservationRepository->getEncounters($dungeon?->id)
            ->map(fn(array $encounter) => $this->buildEncounter($encounter['dungeon_id'], $encounter['encounter_id']))
            ->values();
    }

    public function buildEncounter(int $dungeonId, int $encounterId): NpcEncounterMechanicBuildResult
    {
        $observations = $this->combatLogEncounterObservationRepository->getLatestKillPerRun(
            $dungeonId,
            $encounterId,
            (int)config('keystoneguru.combat_log_encounter_observations.mechanics.run_window'),
        );

        $minRuns = (int)config('keystoneguru.combat_log_encounter_observations.mechanics.min_runs');
        if ($observations->count() < $minRuns) {
            $this->log->buildEncounterTooFewRuns($dungeonId, $encounterId, $observations->count(), $minRuns);

            return new NpcEncounterMechanicBuildResult($dungeonId, $encounterId, $observations->count(), null);
        }

        $npcIds = $observations
            ->flatMap(static fn(CombatLogEncounterObservation $observation) => array_keys($observation->data['npcs'] ?? []))
            ->map(static fn(int|string $npcId) => (int)$npcId)
            ->unique()
            ->values();
        $npcs = $this->npcRepository->findAllByIdWithNpcHealths($npcIds);

        $rows = EncounterMechanicAggregator::fromConfig()->aggregate(
            $observations->map(static fn(CombatLogEncounterObservation $observation) => [
                'key_level' => $observation->key_level,
                'data'      => $observation->data,
            ])->values()->all(),
            array_fill_keys($npcs->filter(static fn(Npc $npc) => $npc->isBoss())->keys()->all(), true),
            $this->getBaseHealthByNpcId($dungeonId, $encounterId, $npcs),
        );

        $this->npcEncounterMechanicRepository->replaceForEncounter($dungeonId, $encounterId, $rows);

        $this->log->buildEncounterBuilt($dungeonId, $encounterId, $observations->count(), count($rows));

        return new NpcEncounterMechanicBuildResult(
            $dungeonId,
            $encounterId,
            $observations->count(),
            array_count_values(array_column($rows, 'type')),
        );
    }

    /**
     * @param  Collection<int, Npc> $npcs
     * @return array<int, float>
     */
    private function getBaseHealthByNpcId(int $dungeonId, int $encounterId, Collection $npcs): array
    {
        $gameVersion = $this->dungeonRepository->find($dungeonId)?->getCurrentMappingVersion(null, null)?->gameVersion;
        if ($gameVersion === null) {
            $this->log->buildEncounterNoGameVersion($dungeonId, $encounterId);

            return [];
        }

        $result = [];
        foreach ($npcs as $npc) {
            $npcHealth = $npc->getHealthByGameVersion($gameVersion);
            if ($npcHealth !== null) {
                $result[$npc->id] = $npcHealth->health * ($npcHealth->percentage ?? 100) / 100;
            }
        }

        return $result;
    }
}
