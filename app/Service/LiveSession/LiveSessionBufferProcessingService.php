<?php

namespace App\Service\LiveSession;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\Structs\IngameXY;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\LiveSession\LiveSessionCombatLogBatchRepositoryInterface;
use App\Repositories\Interfaces\LiveSession\LiveSessionCombatLogBufferRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Stub\DungeonRoute\DungeonRouteRepository as DungeonRouteRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneEnemyRepository as KillZoneEnemyRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneRepository as KillZoneRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneSpellRepository as KillZoneSpellRepositoryStub;
use App\Service\CombatLog\Builders\ResultEventDungeonRouteBuilder;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Filters\BaseCombatLogFilter;
use App\Service\CombatLog\Filters\DungeonRoute\CombatLogDungeonRouteFilter;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\CombatantInfo as CombatantInfoResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged as EnemyEngagedResultEvent;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\CombatLog\Filters\LiveSessionBufferFilterInterface;
use App\Service\LiveSession\CombatLog\Filters\SummonBufferFilter;
use App\Service\LiveSession\Dtos\LiveSessionBufferProcessingResult;
use App\Service\LiveSession\Dtos\LiveSessionStateBroadcastResult;
use App\Service\LiveSession\Logging\LiveSessionBufferProcessingServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class LiveSessionBufferProcessingService implements LiveSessionBufferProcessingServiceInterface
{
    /**
     * An enemy counts as "in combat" only if it was last seen acting within this many seconds of the newest
     * event in the buffer. This ages out enemies that reset/evaded/wiped (which never emit a death event),
     * so they don't show as perpetually in combat.
     */
    private const int IN_COMBAT_WINDOW_SECONDS = 60;

    private const int UNPARSABLE_LINE_LOG_LENGTH = 200;

    public function __construct(
        private readonly CombatLogServiceInterface                          $combatLogService,
        private readonly NpcRepositoryInterface                             $npcRepository,
        private readonly EnemyRepositoryInterface                           $enemyRepository,
        private readonly LiveSessionCombatLogBatchRepositoryInterface       $liveSessionCombatLogBatchRepository,
        private readonly LiveSessionCombatLogBufferRepositoryInterface      $liveSessionCombatLogBufferRepository,
        private readonly CoordinatesServiceInterface                        $coordinatesService,
        private readonly LiveSessionCombatStateServiceInterface             $combatStateService,
        private readonly LiveSessionOverpullDetectionServiceInterface       $overpullDetectionService,
        private readonly LiveSessionStateServiceInterface                   $liveSessionStateService,
        private readonly LiveSessionBufferProcessingServiceLoggingInterface $log,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function processBuffer(LiveSession $liveSession): LiveSessionBufferProcessingResult
    {
        $this->log->processBufferStart($liveSession->id, $liveSession->public_key);

        try {
            $pendingBatches = $this->liveSessionCombatLogBatchRepository->getPendingBatches($liveSession);
            if ($pendingBatches->isEmpty()) {
                $this->log->processBufferNoPendingBatches();

                return new LiveSessionBufferProcessingResult(null, $this->isBroadcastFailed($liveSession));
            }

            $this->log->processBufferPendingBatches($pendingBatches->count(), $pendingBatches->max('sequence'));

            ['batches' => $consumableBatches, 'heldUntil' => $heldUntil] = $this->selectConsumableBatches(
                $pendingBatches,
                $this->liveSessionCombatLogBatchRepository->getHighestConsumedSequence($liveSession),
            );
            if ($consumableBatches->isEmpty()) {
                $this->log->processBufferNoConsumableBatches($heldUntil?->toDateTimeString());

                return new LiveSessionBufferProcessingResult($heldUntil, $this->isBroadcastFailed($liveSession));
            }

            $tmpFile = sprintf(
                '%s/live_session_%d.txt',
                config('keystoneguru.live_sessions.combat_log.tmp_directory'),
                $liveSession->id,
            );

            try {
                $checkpointLineCount = $this->writeCombatLog(
                    $tmpFile,
                    $this->liveSessionCombatLogBufferRepository->findByLiveSession($liveSession),
                    $consumableBatches,
                );
                if ($checkpointLineCount === null) {
                    $this->log->processBufferUnableToWrite();

                    throw new RuntimeException(sprintf('Unable to write the combat log of live session %d to %s', $liveSession->id, $tmpFile));
                }

                $mappingVersion = $liveSession->dungeonRoute->mappingVersion;

                $validNpcIds                 = $this->npcRepository->getInUseNpcIds($mappingVersion);
                $combatLogDungeonRouteFilter = new CombatLogDungeonRouteFilter();
                $combatLogDungeonRouteFilter->setValidNpcIds($validNpcIds);
                $bufferFilters = $this->makeBufferFilters();

                // Track the most recent position per player in the newly ingested lines. Only the latest is
                // persisted, once per chunk. The checkpoint keeps a player's retained casts but not their movement,
                // so a position replayed from it is older than the one an earlier pass already persisted.
                /** @var Collection<string, array{event: AdvancedCombatLogEvent, characterName: string}> $lastKnownPlayerPositions */
                $lastKnownPlayerPositions = collect();

                // When each enemy GUID last produced a combat-log event, to age out enemies that reset/evaded (see
                // {@see self::IN_COMBAT_WINDOW_SECONDS}). Whether an enemy is engaged or dead is the route filter's
                // call; this only says how recently it was seen.
                /** @var Collection<string, Carbon> $lastSeenAtByGuid */
                $lastSeenAtByGuid     = collect();
                $newestEventTimestamp = null;

                /** @var array<string, array{lineNr: int, line: string}> $latestSightingByGuid */
                $latestSightingByGuid = [];

                /** @var array<int, string> $keptLines By line number */
                $keptLines = [];

                $unparsableLineCount = 0;
                /** @var array{lineNr: int, line: string, message: string}|null $firstUnparsableLine */
                $firstUnparsableLine = null;

                $this->combatLogService->parseCombatLogStreaming(
                    $tmpFile,
                    function (BaseEvent $event, int $lineNr) use (
                        $combatLogDungeonRouteFilter,
                        $bufferFilters,
                        $lastKnownPlayerPositions,
                        $validNpcIds,
                        $lastSeenAtByGuid,
                        $checkpointLineCount,
                        &$newestEventTimestamp,
                        &$latestSightingByGuid,
                        &$keptLines
                    ): void {
                        $eventTimestamp = $event->getTimestamp();
                        if ($newestEventTimestamp === null || $eventTimestamp->greaterThan($newestEventTimestamp)) {
                            $newestEventTimestamp = $eventTimestamp;
                        }

                        if ($event instanceof AdvancedCombatLogEvent) {
                            $advancedData = $event->getAdvancedData();

                            $playerPositionOwner = $lineNr > $checkpointLineCount ? self::resolvePlayerPositionOwner($event) : null;
                            if ($playerPositionOwner !== null) {
                                $lastKnownPlayerPositions->put($playerPositionOwner['guid'], [
                                    'event'         => $event,
                                    'characterName' => $playerPositionOwner['characterName'],
                                ]);
                            }

                            // The advanced data describes the info GUID's unit, so this event is a sign of life of it
                            $infoGuid = $advancedData->getInfoGuid();
                            if ($infoGuid instanceof Creature && $validNpcIds->contains($infoGuid->getId())) {
                                if (!$lastSeenAtByGuid->has($infoGuid->getGuid())) {
                                    $this->log->processBufferCombatSighting($eventTimestamp->toString(), $infoGuid->getGuid(), $infoGuid->getId());
                                }

                                $lastSeenAtByGuid->put($infoGuid->getGuid(), $eventTimestamp);
                                $latestSightingByGuid[$infoGuid->getGuid()] = [
                                    'lineNr' => $lineNr,
                                    'line'   => rtrim($event->getRawEvent(), "\r\n"),
                                ];
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
                            $keptLines[$lineNr] = rtrim($event->getRawEvent(), "\r\n");
                        }
                    },
                    // The lines come from the client unvalidated, so one that cannot be parsed is dropped rather than
                    // failing every pass over the batch it is in
                    static function (string $rawEvent, int $lineNr, Throwable $throwable) use (&$unparsableLineCount, &$firstUnparsableLine): void {
                        if ($unparsableLineCount++ === 0) {
                            $firstUnparsableLine = [
                                'lineNr'  => $lineNr,
                                'line'    => Str::limit($rawEvent, self::UNPARSABLE_LINE_LOG_LENGTH),
                                'message' => Str::limit(trim($throwable->getMessage()), self::UNPARSABLE_LINE_LOG_LENGTH),
                            ];
                        }
                    },
                );

                if ($firstUnparsableLine !== null) {
                    $this->log->processBufferSkippedUnparsableLines(
                        $unparsableLineCount,
                        $firstUnparsableLine['lineNr'],
                        $firstUnparsableLine['line'],
                        $firstUnparsableLine['message'],
                    );
                }

                $keptLines = self::keepLatestSightingsOfEngagedEnemies(
                    $keptLines,
                    $latestSightingByGuid,
                    $combatLogDungeonRouteFilter->getEngagedEnemies(),
                );

                $this->processEnemies($liveSession, $mappingVersion, $combatLogDungeonRouteFilter, $lastSeenAtByGuid, $newestEventTimestamp);
                $this->processPlayerPositions(
                    $liveSession,
                    $mappingVersion,
                    $lastKnownPlayerPositions,
                    $this->collectCombatantInfo($combatLogDungeonRouteFilter),
                );

                $isBroadcastFailed = $this->isBroadcastFailed($liveSession);

                $this->saveCheckpoint($liveSession, $keptLines, $consumableBatches);
            } finally {
                @unlink($tmpFile);
            }

            return new LiveSessionBufferProcessingResult($heldUntil, $isBroadcastFailed);
        } finally {
            $this->log->processBufferEnd();
        }
    }

    private function isBroadcastFailed(LiveSession $liveSession): bool
    {
        return $this->liveSessionStateService->broadcastStateIfChanged($liveSession, $liveSession->user) === LiveSessionStateBroadcastResult::Failed;
    }

    /**
     * Split off the batches this pass may consume: every unsequenced batch, and the sequenced batches up to the first
     * missing sequence number. A batch's lines can only be reduced into the checkpoint once every batch before it has
     * been, or a death would be replayed ahead of the sighting it needs and dropped for good. The sequence after the
     * highest consumed one is the next expected; a session that consumed none starts at 0 or 1. A gap is skipped once
     * the oldest batch held back behind it has waited the grace period, so a lost batch cannot stall the session;
     * heldUntil is when that happens for the batches still held back.
     *
     * @param Collection<int, LiveSessionCombatLogBatch> $pendingBatches In replay order
     *
     * @return array{batches: Collection<int, LiveSessionCombatLogBatch>, heldUntil: Carbon|null}
     */
    private function selectConsumableBatches(Collection $pendingBatches, ?int $highestConsumedSequence): array
    {
        [$unsequencedBatches, $sequencedBatches] = $pendingBatches->partition(
            static fn(LiveSessionCombatLogBatch $batch): bool => $batch->sequence === null,
        );

        /** @var Collection<int, LiveSessionCombatLogBatch> $consumableBatches */
        $consumableBatches = $unsequencedBatches->values();
        $sequencedBatches  = $sequencedBatches->values();
        // 1 when nothing was consumed yet; a first batch numbered 0 sits below it and is consumed as such
        $expectedSequence = ($highestConsumedSequence ?? 0) + 1;

        foreach ($sequencedBatches as $index => $batch) {
            if ($batch->sequence > $expectedSequence) {
                $heldBatches = $sequencedBatches->slice($index);
                /** @var Carbon|null $oldestHeldAt */
                $oldestHeldAt = $heldBatches->min('created_at');
                $heldUntil    = $oldestHeldAt?->copy()->addSeconds((int)config('keystoneguru.live_sessions.combat_log.sequence_gap_grace_seconds'));

                if ($heldUntil?->isFuture()) {
                    $this->log->selectConsumableBatchesHeldBack($expectedSequence, $batch->sequence, $heldBatches->count(), $heldUntil->toDateTimeString());

                    return ['batches' => $consumableBatches, 'heldUntil' => $heldUntil];
                }

                $this->log->selectConsumableBatchesSkippedGap($expectedSequence, $batch->sequence, $heldBatches->count());
            }

            $consumableBatches->push($batch);
            $expectedSequence = max($expectedSequence, $batch->sequence + 1);
        }

        return ['batches' => $consumableBatches, 'heldUntil' => null];
    }

    /**
     * Write the checkpoint followed by the pending batches, newline-separated, to the file that is parsed. Returns how
     * many of the file's lines the checkpoint takes up, so every later line number belongs to a pending batch. Null
     * when any part of it could not be written in full, as when the disk is full.
     *
     * @param Collection<int, LiveSessionCombatLogBatch> $pendingBatches
     */
    private function writeCombatLog(
        string                      $tmpFile,
        ?LiveSessionCombatLogBuffer $checkpoint,
        Collection                  $pendingBatches,
    ): ?int {
        $handle = fopen($tmpFile, 'w');
        if ($handle === false) {
            return null;
        }

        $checkpointLineCount = 0;

        try {
            $isFirstPart = true;
            $writePart   = static function (string $decompressed) use ($handle, &$isFirstPart): bool {
                if ($decompressed === '') {
                    return true;
                }

                $part        = $isFirstPart ? $decompressed : "\n" . $decompressed;
                $isFirstPart = false;

                // fwrite() retries a partial write itself and returns short only once a write makes no progress
                return fwrite($handle, $part) === strlen($part);
            };

            if ($checkpoint?->buffer !== null) {
                $decompressed = gzdecode($checkpoint->buffer);
                if ($decompressed === false) {
                    $this->log->writeCombatLogUnableToDecompressCheckpoint();
                } elseif (!$writePart($decompressed)) {
                    return null;
                } elseif ($decompressed !== '') {
                    // The separator in front of the next part ends the checkpoint's last line, terminated or not
                    $checkpointLineCount = substr_count($decompressed, "\n") + 1;
                }
            }

            foreach ($pendingBatches as $batch) {
                $decompressed = $batch->lines === null ? false : gzdecode($batch->lines);
                if ($decompressed === false) {
                    $this->log->writeCombatLogUnableToDecompressBatch($batch->id);
                } elseif (!$writePart($decompressed)) {
                    return null;
                }
            }
        } finally {
            $isClosed = fclose($handle);
        }

        return $isClosed ? $checkpointLineCount : null;
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

            $this->log->processPlayerPositionsSavedPlayerPosition(
                $playerPosition->id,
                $playerPosition->player_guid,
                $playerPosition->character_name,
            );
        }
    }

    /**
     * Resolve the kills and the enemies still in combat to mapped enemies with the auto route creator's own matcher,
     * so a live session marks the enemies the route built from the same log would hold.
     *
     * The enemies still in combat go in alongside the result events as the EnemyEngaged each would get on dying - the
     * same event, so the same position and the same place in the timeline. A kill in a later pass then meets the
     * matcher with the same enemies already taken as in this pass, and resolves to the same enemy. Enemies aged out of
     * the in-combat window are left out instead, so the enemy they held is free again: a boss reset by a wipe is
     * pulled again under a new GUID, and that kill has to be able to take it.
     *
     * @param Collection<string, Carbon> $lastSeenAtByGuid
     */
    private function processEnemies(
        LiveSession                 $liveSession,
        MappingVersion              $mappingVersion,
        CombatLogDungeonRouteFilter $filter,
        Collection                  $lastSeenAtByGuid,
        ?Carbon                     $newestEventTimestamp,
    ): void {
        /** @var Collection<int, EnemyEngagedResultEvent> $inCombatResultEvents */
        $inCombatResultEvents = $this->filterRecentlySeenEnemies($filter->getEngagedEnemies(), $lastSeenAtByGuid, $newestEventTimestamp)
            ->values()
            ->map(static fn(AdvancedCombatLogEvent $engagedEvent): EnemyEngagedResultEvent => new EnemyEngagedResultEvent($engagedEvent));

        $builder = $this->createDungeonRouteBuilder(
            $mappingVersion,
            BaseCombatLogFilter::sortResultEvents($filter->getResultEvents()->values()->concat($inCombatResultEvents)),
        );

        try {
            $builder->build();
        } catch (Throwable $throwable) {
            $this->log->processEnemiesBuildFailed($throwable);

            return;
        }

        $this->overpullDetectionService->processResolvedKills(
            $liveSession,
            $builder->getResolvedKills(),
            $builder->getEnemiesInCombat(),
        );
    }

    /**
     * The builder runs against Stub repositories: the route, its kill zones and their enemies are only scaffolding for
     * the matcher, and must not end up in the database - least of all on the live session's own route.
     *
     * @param Collection<int, BaseResultEvent> $resultEvents
     */
    private function createDungeonRouteBuilder(MappingVersion $mappingVersion, Collection $resultEvents): ResultEventDungeonRouteBuilder
    {
        $dungeonRouteRepository = new DungeonRouteRepositoryStub();

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = $dungeonRouteRepository->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
        $dungeonRoute->setRelation('dungeon', $mappingVersion->dungeon);
        $dungeonRoute->setRelation('mappingVersion', $mappingVersion);
        $dungeonRoute->setRelation('killZones', collect());

        return new ResultEventDungeonRouteBuilder(
            $this->coordinatesService,
            $dungeonRouteRepository,
            new KillZoneRepositoryStub(),
            new KillZoneEnemyRepositoryStub(),
            new KillZoneSpellRepositoryStub(),
            $this->enemyRepository,
            $this->npcRepository,
            $dungeonRoute,
            $resultEvents,
        );
    }

    /**
     * Drop the engaged enemies that were last seen longer than the in-combat window before the newest event: they
     * reset, evaded or outlived a wipe, none of which logs a death.
     *
     * @param Collection<string, AdvancedCombatLogEvent> $engagedEnemies   By GUID
     * @param Collection<string, Carbon>                 $lastSeenAtByGuid
     *
     * @return Collection<string, AdvancedCombatLogEvent>
     */
    private function filterRecentlySeenEnemies(
        Collection $engagedEnemies,
        Collection $lastSeenAtByGuid,
        ?Carbon    $newestEventTimestamp,
    ): Collection {
        $this->log->filterRecentlySeenEnemiesStart($engagedEnemies->count(), $newestEventTimestamp?->toString());

        /** @var Collection<string, AdvancedCombatLogEvent> $recentlySeenEnemies */
        $recentlySeenEnemies = collect();

        try {
            if ($newestEventTimestamp === null) {
                $this->log->filterRecentlySeenEnemiesNewestEventTimestampNull();

                return $recentlySeenEnemies;
            }

            $cutoff = $newestEventTimestamp->copy()->subSeconds(self::IN_COMBAT_WINDOW_SECONDS);

            foreach ($engagedEnemies as $guid => $engagedEvent) {
                $lastSeenAt = $lastSeenAtByGuid->get($guid);
                if ($lastSeenAt === null || $lastSeenAt->lessThan($cutoff)) {
                    $this->log->filterRecentlySeenEnemiesTimedOut($guid, $lastSeenAt?->toString());

                    continue;
                }

                $recentlySeenEnemies->put($guid, $engagedEvent);
            }

            return $recentlySeenEnemies;
        } finally {
            $this->log->filterRecentlySeenEnemiesEnd($recentlySeenEnemies->count());
        }
    }

    /**
     * Add the latest sighting of every enemy still engaged to the kept lines, in log order. The route filter keeps
     * only an enemy's first sighting, and without its latest one the next pass would age the enemy out of the
     * in-combat window by its first sighting instead. Replayed, the line is an ordinary later sighting the filters
     * drop again; an enemy that died or left the engaged enemies carries none forward.
     *
     * @param array<int, string>                              $keptLines            By line number
     * @param array<string, array{lineNr: int, line: string}> $latestSightingByGuid
     * @param Collection<string, AdvancedCombatLogEvent>      $engagedEnemies       By GUID
     *
     * @return array<int, string> By line number, in log order
     */
    private static function keepLatestSightingsOfEngagedEnemies(
        array      $keptLines,
        array      $latestSightingByGuid,
        Collection $engagedEnemies,
    ): array {
        foreach ($engagedEnemies->keys() as $guid) {
            $latestSighting = $latestSightingByGuid[$guid] ?? null;
            if ($latestSighting === null) {
                continue;
            }

            $keptLines[$latestSighting['lineNr']] ??= $latestSighting['line'];
        }

        ksort($keptLines);

        return $keptLines;
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
