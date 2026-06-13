<?php

namespace App\Service\LiveSession;

use App\Events\Models\LiveSession\EnemyKilledEvent;
use App\Events\Models\LiveSession\PlayerMovedEvent;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\Structs\IngameXY;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Filters\DungeonRoute\CombatLogDungeonRouteFilter;
use App\Service\CombatLog\ResultEvents\EnemyEngaged as EnemyEngagedResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyKilled as EnemyKilledResultEvent;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use Throwable;

class LiveSessionBufferProcessingService implements LiveSessionBufferProcessingServiceInterface
{
    public function __construct(
        private readonly CombatLogServiceInterface              $combatLogService,
        private readonly NpcRepositoryInterface                 $npcRepository,
        private readonly EnemyRepositoryInterface               $enemyRepository,
        private readonly CoordinatesServiceInterface            $coordinatesService,
        private readonly LiveSessionCombatStateServiceInterface $combatStateService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function processBuffer(LiveSession $liveSession): void
    {
        $buffer = $liveSession->combatLogBuffer;
        if ($buffer === null || $buffer->buffer === null) {
            return;
        }

        $decompressed = gzdecode($buffer->buffer);
        if ($decompressed === false || $decompressed === '') {
            return;
        }

        $tmpFile = sprintf('/dev/shm/live_session_%d.txt', $liveSession->id);
        file_put_contents($tmpFile, $decompressed);

        try {
            /** @var MappingVersion $mappingVersion */
            $mappingVersion = $liveSession->dungeonRoute->mappingVersion;

            $validNpcIds                 = $this->npcRepository->getInUseNpcIds($mappingVersion);
            $combatLogDungeonRouteFilter = new CombatLogDungeonRouteFilter();
            $combatLogDungeonRouteFilter->setValidNpcIds($validNpcIds);

            /** @var Collection<string, array{event: AdvancedCombatLogEvent, characterName: string}> $lastKnownPlayerPositions */
            $lastKnownPlayerPositions = collect();

            $this->combatLogService->parseCombatLogStreaming(
                $tmpFile,
                function (BaseEvent $event, int $lineNr) use ($combatLogDungeonRouteFilter, $lastKnownPlayerPositions): void {
                    if ($event instanceof AdvancedCombatLogEvent) {
                        $sourceGuid = $event->getGenericData()->getSourceGuid();
                        if ($sourceGuid instanceof Player) {
                            $lastKnownPlayerPositions->put($sourceGuid->getGuid(), [
                                'event'         => $event,
                                'characterName' => $event->getGenericData()->getSourceName(),
                            ]);
                        }
                    }

                    try {
                        $combatLogDungeonRouteFilter->parse($event, $lineNr);
                    } catch (Throwable) {
                    }
                },
            );

            $this->processKilledEnemies($liveSession, $mappingVersion, $combatLogDungeonRouteFilter);
            $this->processPlayerPositions($liveSession, $mappingVersion, $lastKnownPlayerPositions);
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * @param Collection<string, array{event: AdvancedCombatLogEvent, characterName: string}> $lastKnownPlayerPositions
     */
    private function processPlayerPositions(
        LiveSession    $liveSession,
        MappingVersion $mappingVersion,
        Collection     $lastKnownPlayerPositions,
    ): void {
        if ($lastKnownPlayerPositions->isEmpty()) {
            return;
        }

        $dungeonFloors = $mappingVersion->dungeon->floors->keyBy('ui_map_id');
        $defaultFloor  = $mappingVersion->dungeon->floors->firstWhere('default', 1);

        foreach ($lastKnownPlayerPositions as $guidStr => $data) {
            /** @var AdvancedCombatLogEvent $event */
            $event        = $data['event'];
            $advancedData = $event->getAdvancedData();

            $floor = $dungeonFloors->get($advancedData->getUiMapId()) ?? $defaultFloor;
            if ($floor === null) {
                continue;
            }

            $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                new IngameXY($advancedData->getPositionX(), $advancedData->getPositionY(), $floor),
            );

            $playerPosition = $this->combatStateService->setPlayerPosition(
                $liveSession,
                $guidStr,
                $data['characterName'],
                $latLng->getLat(),
                $latLng->getLng(),
                $floor->id,
            );

            $playerPosition->setRelation('floor', $floor);

            broadcast(new PlayerMovedEvent(
                $this->coordinatesService,
                $liveSession,
                $liveSession->user,
                $playerPosition,
            ));
        }
    }

    private function processKilledEnemies(
        LiveSession                 $liveSession,
        MappingVersion              $mappingVersion,
        CombatLogDungeonRouteFilter $filter,
    ): void {
        $resultEvents     = $filter->getResultEvents();
        $availableEnemies = $this->enemyRepository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion)->keyBy('id');

        /** @var Collection<string, EnemyEngagedResultEvent> $pendingEngaged */
        $pendingEngaged = collect();

        foreach ($resultEvents as $resultEvent) {
            if ($resultEvent instanceof EnemyEngagedResultEvent) {
                $pendingEngaged->put($resultEvent->getGuid()->getGuid(), $resultEvent);
            } elseif ($resultEvent instanceof EnemyKilledResultEvent) {
                $guid    = $resultEvent->getGuid();
                $engaged = $pendingEngaged->get($guid->getGuid());
                $pendingEngaged->forget($guid->getGuid());

                if ($engaged === null) {
                    continue;
                }

                $enemy = $this->resolveEnemy(
                    $guid->getId(),
                    $engaged->getEngagedEvent()->getAdvancedData()->getPositionX(),
                    $engaged->getEngagedEvent()->getAdvancedData()->getPositionY(),
                    $engaged->getEngagedEvent()->getAdvancedData()->getUiMapId(),
                    $availableEnemies,
                    $mappingVersion,
                );

                if ($enemy === null) {
                    continue;
                }

                $availableEnemies->forget($enemy->id);

                $isNew = $this->combatStateService->setKilledEnemy($liveSession, $enemy->npc_id, $enemy->mdt_id);

                if ($isNew) {
                    broadcast(new EnemyKilledEvent($this->coordinatesService, $liveSession, $liveSession->user, $enemy));
                }
            }
        }
    }

    /**
     * @param Collection<int, Enemy> $availableEnemies
     */
    private function resolveEnemy(
        int            $npcId,
        float          $ingameX,
        float          $ingameY,
        int            $uiMapId,
        Collection     $availableEnemies,
        MappingVersion $mappingVersion,
    ): ?Enemy {
        /** @var Collection<int, Enemy> $candidates */
        $candidates = $availableEnemies->filter(static fn(Enemy $e) => $e->npc_id === $npcId);

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $floor = $mappingVersion->dungeon->floors->firstWhere('ui_map_id', $uiMapId)
            ?? $mappingVersion->dungeon->floors->firstWhere('default', 1);

        if ($floor === null) {
            return $candidates->first();
        }

        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
            new IngameXY($ingameX, $ingameY, $floor),
        );
        $closest = null;
        $minDist = PHP_FLOAT_MAX;

        foreach ($candidates as $enemy) {
            $dist = ($enemy->lat - $latLng->getLat()) ** 2 + ($enemy->lng - $latLng->getLng()) ** 2;
            if ($dist < $minDist) {
                $minDist = $dist;
                $closest = $enemy;
            }
        }

        return $closest;
    }
}
