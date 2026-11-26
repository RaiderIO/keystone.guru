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
use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\LiveSession\LiveSessionCombatLogBatchRepositoryInterface;
use App\Repositories\Interfaces\LiveSession\LiveSessionCombatLogBufferRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Filters\DungeonRoute\CombatLogDungeonRouteFilter;
use App\Service\CombatLog\Filters\UnitDefeatedFilter;
use App\Service\CombatLog\ResultEvents\CombatantInfo as CombatantInfoResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged as EnemyEngagedResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyKilled as EnemyKilledResultEvent;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\CombatLog\Filters\LiveSessionBufferFilterInterface;
use App\Service\LiveSession\CombatLog\Filters\SummonBufferFilter;
use App\Service\LiveSession\Logging\LiveSessionBufferProcessingServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        private readonly NpcRepositoryInterface                             $npcRepository,
        private readonly EnemyRepositoryInterface                           $enemyRepository,
        private readonly LiveSessionCombatLogBatchRepositoryInterface       $liveSessionCombatLogBatchRepository,
        private readonly LiveSessionCombatLogBufferRepositoryInterface      $liveSessionCombatLogBufferRepository,
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
            $pendingBatches = $this->liveSessionCombatLogBatchRepository->getPendingBatches($liveSession);
            if ($pendingBatches->isEmpty()) {
                $this->log->processBufferNoPendingBatches();

                return;
            }

            $this->log->processBufferPendingBatches($pendingBatches->count(), $pendingBatches->max('sequence'));

            $tmpFile = sprintf('/dev/shm/live_session_%d.txt', $liveSession->id);
            if (!$this->writeCombatLog(
                $tmpFile,
                $this->liveSessionCombatLogBufferRepository->findByLiveSession($liveSession),
                $pendingBatches,
            )) {
                $this->log->processBufferUnableToWrite();

                return;
            }

            try {
                $mappingVersion = $liveSession->dungeonRoute->mappingVersion;

                $validNpcIds                 = $this->npcRepository->getInUseNpcIds($mappingVersion);
                $unitDefeatedFilter          = new UnitDefeatedFilter();
                $combatLogDungeonRouteFilter = new CombatLogDungeonRouteFilter();
                $combatLogDungeonRouteFilter->setValidNpcIds($validNpcIds);
                $bufferFilters = $this->makeBufferFilters();

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

                /** @var array<int, string> $keptLines */
                $keptLines = [];

                $this->combatLogService->parseCombatLogStreaming(
                    $tmpFile,
                    function (BaseEvent $event, int $lineNr) use (
                        $unitDefeatedFilter,
                        $combatLogDungeonRouteFilter,
                        $bufferFilters,
                        $lastKnownPlayerPositions,
                        $validNpcIds,
                        $inCombatSightings,
                        &$newestEventTimestamp,
                        &$keptLines
                    ): void {
                        $eventTimestamp = $event->getTimestamp();
                        if ($newestEventTimestamp === null || $eventTimestamp->greaterThan($newestEventTimestamp)) {
                            $newestEventTimestamp = $eventTimestamp;
                        }

                        if ($event instanceof AdvancedCombatLogEvent) {
                            $advancedData = $event->getAdvancedData();

                            $playerPositionOwner = self::resolvePlayerPositionOwner($event);
                            if ($playerPositionOwner !== null) {
                                $lastKnownPlayerPositions->put($playerPositionOwner['guid'], [
                                    'event'         => $event,
                                    'characterName' => $playerPositionOwner['characterName'],
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

                        // A dead enemy leaves combat.
                        if ($unitDefeatedFilter->parse($event, $lineNr)) {
                            $this->log->processBufferPreCombatRemoval($event->getRawEvent());

                            // May be an NPC, a Player, whatever, just forget it
                            if ($event instanceof CombatLogEvent || $event instanceof GenericSpecialEvent) {
                                $infoGuid = $event->getGenericData()->getDestGuid();
                                $inCombatSightings->forget($infoGuid->getGuid());
                                $this->log->processBufferCombatRemoval($eventTimestamp->toString(), $infoGuid->getGuid());
                            }
                        }

                        // The route filter is the primary keep signal for the next checkpoint: everything it acts on
                        // (dungeon context, enemy sightings, kills, player deaths, tracked spell casts) is replayed
                        // in front of the next batches to reconstruct the same in-flight state.
                        $isKept = false;

                        try {
                            $isKept = $combatLogDungeonRouteFilter->parse($event, $lineNr);
                        } catch (Throwable) {
                        }

                        if (!$isKept) {
                            foreach ($bufferFilters as $bufferFilter) {
                                if ($bufferFilter->shouldKeep($event)) {
                                    $isKept = true;

                                    break;
                                }
                            }
                        }

                        if ($isKept) {
                            $keptLines[] = rtrim($event->getRawEvent(), "\r\n");
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

                $this->saveCheckpoint($liveSession, $keptLines, $pendingBatches);
            } finally {
                @unlink($tmpFile);
            }
        } finally {
            $this->log->processBufferEnd();
        }
    }

    /**
     * Write the checkpoint followed by the pending batches, newline-separated, to the file that is parsed.
     *
     * @param Collection<int, LiveSessionCombatLogBatch> $pendingBatches
     */
    private function writeCombatLog(
        string                      $tmpFile,
        ?LiveSessionCombatLogBuffer $checkpoint,
        Collection                  $pendingBatches,
    ): bool {
        $handle = fopen($tmpFile, 'w');
        if ($handle === false) {
            return false;
        }

        try {
            $isFirstPart = true;
            $writePart   = static function (string $decompressed) use ($handle, &$isFirstPart): bool {
                if ($decompressed === '') {
                    return true;
                }

                $written     = fwrite($handle, $isFirstPart ? $decompressed : "\n" . $decompressed);
                $isFirstPart = false;

                return $written !== false;
            };

            if ($checkpoint?->buffer !== null) {
                $decompressed = gzdecode($checkpoint->buffer);
                if ($decompressed === false) {
                    $this->log->writeCombatLogUnableToDecompressCheckpoint();
                } elseif (!$writePart($decompressed)) {
                    return false;
                }
            }

            foreach ($pendingBatches as $batch) {
                $decompressed = $batch->lines === null ? false : gzdecode($batch->lines);
                if ($decompressed === false) {
                    $this->log->writeCombatLogUnableToDecompressBatch($batch->id);
                } elseif (!$writePart($decompressed)) {
                    return false;
                }
            }
        } finally {
            fclose($handle);
        }

        return true;
    }

    /**
     * Replace the checkpoint with the kept lines and consume the batches that went into them, atomically. Until
     * this commits nothing is consumed, so a run that dies earlier is simply redone by the next run - the state
     * it already wrote is written idempotently. A batch stored after the pending batches were read is not among
     * $consumedBatches and stays pending for the next run.
     *
     * @param array<int, string>                         $keptLines
     * @param Collection<int, LiveSessionCombatLogBatch> $consumedBatches
     */
    private function saveCheckpoint(LiveSession $liveSession, array $keptLines, Collection $consumedBatches): void
    {
        $compressed = gzencode(implode("\n", $keptLines), 6);
        if ($compressed === false) {
            $this->log->saveCheckpointUnableToCompress();

            return;
        }

        /** @var Collection<int, int> $consumedBatchIds */
        $consumedBatchIds = $consumedBatches->pluck('id');

        DB::transaction(function () use ($liveSession, $compressed, $consumedBatchIds): void {
            $this->liveSessionCombatLogBufferRepository->saveCheckpoint($liveSession, $compressed);
            $this->liveSessionCombatLogBatchRepository->markConsumed($consumedBatchIds);
        }, 3);
    }

    /**
     * The extra reasons - beyond auto-route-creator relevance - to keep an event in the checkpoint. Filters are
     * stateful per pass, so a fresh set is created on every call.
     *
     * Player movement is intentionally not kept: the latest position per player is persisted every pass (see
     * {@see self::processPlayerPositions()}), so a movement trail in the checkpoint would only bloat it.
     *
     * @return array<int, LiveSessionBufferFilterInterface>
     */
    private function makeBufferFilters(): array
    {
        return [
            new SummonBufferFilter(),
        ];
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

        // Per-floor lat/lng share the same coordinate space, so a same-npc_id candidate on a different
        // floor can be numerically closer than the real match - restrict to the resolved floor first.
        /** @var Collection<int, Enemy> $candidatesOnFloor */
        $candidatesOnFloor = $candidates->filter(static fn(Enemy $e) => $e->floor_id === $floor->id);
        if ($candidatesOnFloor->isNotEmpty()) {
            $candidates = $candidatesOnFloor;
        }

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $killLocation = new IngameXY($ingameX, $ingameY, $floor);
        $closest      = null;
        $minDist      = PHP_FLOAT_MAX;

        foreach ($candidates as $enemy) {
            $enemyFloor    = $mappingVersion->dungeon->floors->firstWhere('id', $enemy->floor_id) ?? $floor;
            $enemyLocation = $this->coordinatesService->calculateIngameLocationForMapLocation(
                new LatLng($enemy->lat, $enemy->lng, $enemyFloor),
            );

            $dist = $this->coordinatesService->distanceIngameXY($killLocation, $enemyLocation);
            if ($dist < $minDist) {
                $minDist = $dist;
                $closest = $enemy;
            }
        }

        return $closest;
    }

    /**
     * The advanced data carries the position of the info GUID's unit, which is the source on most events but
     * not all of them - so the player a position belongs to is the info GUID, named through whichever side of
     * the event it is on. Null when the info GUID is not a player, or is neither the source nor the dest.
     *
     * @return array{guid: string, characterName: string}|null
     */
    private static function resolvePlayerPositionOwner(AdvancedCombatLogEvent $event): ?array
    {
        $infoGuid = $event->getAdvancedData()->getInfoGuid();
        if (!($infoGuid instanceof Player)) {
            return null;
        }

        $genericData   = $event->getGenericData();
        $characterName = match ($infoGuid->getGuid()) {
            $genericData->getSourceGuid()?->getGuid() => $genericData->getSourceName(),
            $genericData->getDestGuid()?->getGuid()   => $genericData->getDestName(),
            default                                   => null,
        };

        if ($characterName === null) {
            return null;
        }

        return [
            'guid'          => $infoGuid->getGuid(),
            'characterName' => $characterName,
        ];
    }
}
