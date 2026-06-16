<?php

namespace App\Service\LiveSession;

use App\Events\Models\LiveSession\PlayerMovedEvent;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\GenericSpecialEvent;
use App\Logic\Structs\IngameXY;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Filters\DungeonRoute\CombatLogDungeonRouteFilter;
use App\Service\CombatLog\Filters\UnitDefeatedFilter;
use App\Service\CombatLog\ResultEvents\CombatantInfo as CombatantInfoResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged as EnemyEngagedResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyKilled as EnemyKilledResultEvent;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\Logging\LiveSessionBufferProcessingServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class LiveSessionBufferProcessingService implements LiveSessionBufferProcessingServiceInterface
{
    /**
     * An enemy counts as "in combat" only if it was last seen acting within this many seconds of the newest
     * event in the buffer. This ages out enemies that reset/evaded/wiped (which never emit a death event),
     * so they don't show as perpetually in combat.
     */
    private const int IN_COMBAT_WINDOW_SECONDS = 60;

    public function __construct(
        private readonly CombatLogServiceInterface                          $combatLogService,
        private readonly LiveSessionCombatLogServiceInterface               $liveSessionCombatLogService,
        private readonly NpcRepositoryInterface                             $npcRepository,
        private readonly EnemyRepositoryInterface                           $enemyRepository,
        private readonly CoordinatesServiceInterface                        $coordinatesService,
        private readonly LiveSessionCombatStateServiceInterface             $combatStateService,
        private readonly LiveSessionOverpullDetectionServiceInterface       $overpullDetectionService,
        private readonly LiveSessionBufferProcessingServiceLoggingInterface $log,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function processBuffer(LiveSession $liveSession): void
    {
        $this->log->processBufferStart($liveSession->id, $liveSession->public_key);

        try {
            $buffer = $liveSession->combatLogBuffer;
            if ($buffer === null || $buffer->buffer === null) {
                $this->log->processBufferBufferIsNull();

                return;
            }

            $this->log->addContext('sequenceNumber', [
                'value' => $buffer->last_sequence,
            ]);

            $decompressed = gzdecode($buffer->buffer);
            if ($decompressed === false || $decompressed === '') {
                $this->log->processBufferUnableToDecompress();

                return;
            }

            // Captured before processing so the post-reduction write can be skipped if a newer batch arrived
            // in the meantime (it would have bumped last_sequence), preventing us from clobbering those lines.
            $lastSequenceAtRead = $buffer->last_sequence;

            $tmpFile        = sprintf('/dev/shm/live_session_%d.txt', $liveSession->id);
            $fileSaveResult = file_put_contents($tmpFile, $decompressed);
            if (!$fileSaveResult) {
                $this->log->processBufferUnableToWrite();

                return;
            }
            unset($decompressed);

            try {
                $mappingVersion = $liveSession->dungeonRoute->mappingVersion;

                $validNpcIds                 = $this->npcRepository->getInUseNpcIds($mappingVersion);
                $unitDefeatedFilter          = new UnitDefeatedFilter();
                $combatLogDungeonRouteFilter = new CombatLogDungeonRouteFilter();
                $combatLogDungeonRouteFilter->setValidNpcIds($validNpcIds);

                // Track the most recent position per player across the full buffer (reduced lines + the newly
                // ingested ones). Only the latest is persisted, once per chunk - movement is not kept in the
                // buffer, so this is the single source of truth for live player dots.
                /** @var Collection<string, array{event: AdvancedCombatLogEvent, characterName: string}> $lastKnownPlayerPositions */
                $lastKnownPlayerPositions = collect();

                // Track the last-seen sighting per enemy GUID across the buffer. An enemy is kept here while it is
                // actively producing combat-log events and removed when it dies; the timestamp is used to age out
                // enemies that reset/evaded (see {@see self::IN_COMBAT_WINDOW_SECONDS}).
                /** @var Collection<string, array{npcId: int, x: float, y: float, uiMapId: int, timestamp: Carbon}> $inCombatSightings */
                $inCombatSightings    = collect();
                $newestEventTimestamp = null;

                $this->combatLogService->parseCombatLogStreaming(
                    $tmpFile,
                    function (BaseEvent $event, int $lineNr) use (
                        $unitDefeatedFilter,
                        $combatLogDungeonRouteFilter,
                        $lastKnownPlayerPositions,
                        $validNpcIds,
                        $inCombatSightings,
                        &$newestEventTimestamp
                    ): void {
                        $eventTimestamp = $event->getTimestamp();
                        if ($newestEventTimestamp === null || $eventTimestamp->greaterThan($newestEventTimestamp)) {
                            $newestEventTimestamp = $eventTimestamp;
                        }

                        if ($event instanceof AdvancedCombatLogEvent) {
                            $advancedData = $event->getAdvancedData();

                            $sourceGuid = $event->getGenericData()->getSourceGuid();
                            if ($sourceGuid instanceof Player) {
                                $lastKnownPlayerPositions->put($sourceGuid->getGuid(), [
                                    'event'         => $event,
                                    'characterName' => $event->getGenericData()->getSourceName(),
                                ]);
                            }

                            // The advanced data describes the info GUID's unit (incl. its position), so when that
                            // is a valid enemy creature this is a fresh sighting of an enemy we are fighting.
                            $infoGuid = $advancedData->getInfoGuid();
                            if ($infoGuid instanceof Creature && $validNpcIds->contains($infoGuid->getId())) {
                                $existingInCombatSighting = $inCombatSightings->get($infoGuid->getGuid());
                                if ($existingInCombatSighting === null) {
                                    $this->log->processBufferCombatSighting($eventTimestamp->toString(), $infoGuid->getGuid(), $infoGuid->getId());

                                    $inCombatSightings->put($infoGuid->getGuid(), [
                                        'npcId'     => $infoGuid->getId(),
                                        'x'         => $advancedData->getPositionX(),
                                        'y'         => $advancedData->getPositionY(),
                                        'uiMapId'   => $advancedData->getUiMapId(),
                                        'timestamp' => $eventTimestamp,
                                    ]);
                                } else {
                                    // Update just the timestamp - not the rest
                                    $inCombatSightings->put($infoGuid->getGuid(), array_merge($existingInCombatSighting, [
                                        'timestamp' => $eventTimestamp,
                                    ]));
                                }
                            }
                        }

                        // A dead enemy is no longer in combat.
                        if ($unitDefeatedFilter->parse($event, $lineNr)) {
                            $this->log->processBufferPreCombatRemoval($event->getRawEvent());

                            // May be an NPC, a Player, whatever, just forget it
                            if ($event instanceof CombatLogEvent || $event instanceof GenericSpecialEvent) {
                                $infoGuid = $event->getGenericData()->getDestGuid();
                                $inCombatSightings->forget($infoGuid->getGuid());
                                $this->log->processBufferCombatRemoval($eventTimestamp->toString(), $infoGuid->getGuid());
                            }
                        }

                        try {
                            $combatLogDungeonRouteFilter->parse($event, $lineNr);
                        } catch (Throwable) {
                        }
                    },
                );

                $this->processKilledEnemies($liveSession, $mappingVersion, $combatLogDungeonRouteFilter, $inCombatSightings, $newestEventTimestamp);
                $this->processPlayerPositions(
                    $liveSession,
                    $mappingVersion,
                    $lastKnownPlayerPositions,
                    $this->collectCombatantInfo($combatLogDungeonRouteFilter),
                );

                $this->reduceBuffer($liveSession, $tmpFile, $validNpcIds, $lastSequenceAtRead);
            } finally {
                @unlink($tmpFile);
            }
        } finally {
            $this->log->processBufferEnd();
        }
    }

    /**
     * Shrink the stored buffer to the minimal set of lines needed to keep re-running the auto-route
     * creator, so it does not grow unbounded with every batch. Player positions are persisted separately
     * (see {@see processPlayerPositions()}), so player movement is intentionally not retained here.
     *
     * The write is conditional on `last_sequence` being unchanged since we read the buffer: if a newer
     * batch was appended while this job ran, we leave the buffer alone and let the next job reduce it,
     * so freshly ingested lines are never overwritten. Re-processing is idempotent, so nothing is lost.
     *
     * @param Collection<int, int> $validNpcIds
     */
    private function reduceBuffer(
        LiveSession $liveSession,
        string      $tmpFile,
        Collection  $validNpcIds,
        ?int        $lastSequenceAtRead,
    ): void {
        $reducedLines = $this->liveSessionCombatLogService->reduceCombatLogForBuffer($tmpFile, $validNpcIds);
        $compressed   = gzencode(implode("\n", $reducedLines), 6);
        if ($compressed === false) {
            $this->log->reduceBufferUnableToCompress();

            return;
        }

        $query = LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id);
        if ($lastSequenceAtRead === null) {
            $query->whereNull('last_sequence');
        } else {
            $query->where('last_sequence', $lastSequenceAtRead);
        }

        $query->update(['buffer' => $compressed]);
    }

    /**
     * Index the most recent COMBATANT_INFO result event per player GUID.
     *
     * @return Collection<string, CombatantInfoResultEvent>
     */
    private function collectCombatantInfo(CombatLogDungeonRouteFilter $filter): Collection
    {
        /** @var Collection<string, CombatantInfoResultEvent> $combatantInfo */
        $combatantInfo = collect();

        foreach ($filter->getResultEvents() as $resultEvent) {
            if ($resultEvent instanceof CombatantInfoResultEvent) {
                $combatantInfo->put($resultEvent->getGuid()->getGuid(), $resultEvent);
            }
        }

        return $combatantInfo;
    }

    /**
     * @param Collection<string, array{event: AdvancedCombatLogEvent, characterName: string}> $lastKnownPlayerPositions
     * @param Collection<string, CombatantInfoResultEvent>                                    $combatantInfoByGuid
     */
    private function processPlayerPositions(
        LiveSession    $liveSession,
        MappingVersion $mappingVersion,
        Collection     $lastKnownPlayerPositions,
        Collection     $combatantInfoByGuid,
    ): void {
        if ($lastKnownPlayerPositions->isEmpty()) {
            $this->log->processPlayerPositionsNoLastKnownPlayerPositions();

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

            $classId          = null;
            $specializationId = null;
            $combatantInfo    = $combatantInfoByGuid->get($guidStr);
            if ($combatantInfo !== null) {
                try {
                    $classId          = $combatantInfo->getClass()->class_id;
                    $specializationId = $combatantInfo->getSpecialization()->specialization_id;
                } catch (Throwable) {
                    // SpecId is already null, reset class Id too for consistency
                    $classId = null;
                }
            }

            $playerPosition = $this->combatStateService->setPlayerPosition(
                $liveSession,
                $guidStr,
                $data['characterName'],
                $latLng->getLat(),
                $latLng->getLng(),
                $floor->id,
                $classId,
                $specializationId,
            );

            $playerPosition->setRelation('floor', $floor);

            broadcast(new PlayerMovedEvent(
                $this->coordinatesService,
                $liveSession,
                $liveSession->user,
                $playerPosition,
            ));
            $this->log->processPlayerPositionsBroadcastPlayerMovedEvent(
                $playerPosition->id,
                $playerPosition->player_guid,
                $playerPosition->character_name,
            );
        }
    }

    /**
     * @param Collection<string, array{npcId: int, x: float, y: float, uiMapId: int, timestamp: Carbon}> $inCombatSightings
     */
    private function processKilledEnemies(
        LiveSession                 $liveSession,
        MappingVersion              $mappingVersion,
        CombatLogDungeonRouteFilter $filter,
        Collection                  $inCombatSightings,
        ?Carbon                     $newestEventTimestamp,
    ): void {
        $resultEvents     = $filter->getResultEvents();
        $availableEnemies = $this->enemyRepository->getAvailableEnemiesForDungeonRouteBuilder($mappingVersion)->keyBy('id');

        /** @var Collection<string, EnemyEngagedResultEvent> $pendingEngaged */
        $pendingEngaged = collect();

        /** @var Collection<int, Enemy> $resolvedKills */
        $resolvedKills = collect();

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
                $resolvedKills->push($enemy);
            }
        }

        $inCombatEnemies = $this->resolveInCombatEnemies(
            $inCombatSightings,
            $newestEventTimestamp,
            $availableEnemies,
            $mappingVersion,
        );

        $this->overpullDetectionService->processResolvedKills($liveSession, $resolvedKills, $inCombatEnemies);
    }

    /**
     * Resolve the still-active enemy sightings to route enemies. Sightings older than the in-combat window
     * are discarded (reset/evaded enemies). Resolution runs against the pool left after kill resolution, so
     * already-killed enemies are never matched, and matched enemies are forgotten to avoid two sightings of
     * the same npc resolving to the same enemy.
     *
     * @param Collection<string, array{npcId: int, x: float, y: float, uiMapId: int, timestamp: Carbon}> $inCombatSightings
     * @param Collection<int, Enemy>                                                                     $availableEnemies
     *
     * @return Collection<int, Enemy>
     */
    private function resolveInCombatEnemies(
        Collection     $inCombatSightings,
        ?Carbon        $newestEventTimestamp,
        Collection     $availableEnemies,
        MappingVersion $mappingVersion,
    ): Collection {
        $this->log->resolveInCombatEnemiesStart($inCombatSightings->count(), $availableEnemies->count(), $newestEventTimestamp?->toString());

        /** @var Collection<int, Enemy> $resolved */
        $resolved = collect();
        try {

            if ($newestEventTimestamp === null) {
                $this->log->resolveInCombatEnemiesNewestEventTimestampNull();

                return $resolved;
            }

            $cutoff = $newestEventTimestamp->copy()->subSeconds(self::IN_COMBAT_WINDOW_SECONDS);

            foreach ($inCombatSightings as $sighting) {
                if ($sighting['timestamp']->lessThan($cutoff)) {
                    $this->log->resolveInCombatEnemiesTimedOut($sighting);

                    continue;
                }

                $enemy = $this->resolveEnemy(
                    $sighting['npcId'],
                    $sighting['x'],
                    $sighting['y'],
                    $sighting['uiMapId'],
                    $availableEnemies,
                    $mappingVersion,
                );

                if ($enemy === null) {
                    $this->log->resolveInCombatEnemiesUnableToResolveEnemy($sighting);

                    continue;
                }

                $availableEnemies->forget($enemy->id);
                $resolved->push($enemy);
            }

            return $resolved;
        } finally {
            $this->log->resolveInCombatEnemiesEnd($resolved->count());
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
