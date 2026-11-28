<?php

namespace Tests\Feature\App\Service\LiveSession;

use App\Events\LiveSession\LiveSessionStateChangedEvent;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcId;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\LiveSessionBufferProcessingService;
use App\Service\LiveSession\LiveSessionOverpullDetectionServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;
use ZipArchive;

/**
 * A live session resolves its kills and its enemies in combat with the auto route creator's own matcher, so it marks
 * the enemies the route built from the same log holds - no matter how many passes the log arrives in.
 */
#[Group('LiveSession')]
#[Group('LiveSessionBufferProcessingService')]
#[SlowTest]
final class LiveSessionBufferProcessingServiceEnemyResolutionTest extends PublicTestCase
{
    /** @var string The player dealing all the damage in a synthetic log; only its GUID being a player matters */
    private const string PLAYER_GUID = 'Player-4184-00867C26';

    /** @var string The month/day every synthetic log line is stamped with */
    private const string LOG_DATE = '8/21';

    private const int CHUNK_COUNT = 5;

    // -------------------------------------------------------------------------
    // Real logs: the route creator is the oracle
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('realCombatLogProvider')]
    public function processBuffer_givenARealCombatLog_marksTheEnemiesTheRouteCreatorBuildsFromIt(string $eventsFile): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $lines   = $this->readCombatLogFixture($eventsFile);
        $dungeon = $this->findDungeonForCombatLog($lines);

        $logFilePath = storage_path(sprintf('app/%s_events.txt', uniqid('live-session-oracle-', true)));
        file_put_contents($logFilePath, implode("\n", $lines) . "\n");

        $maxDungeonRouteId = (int)DungeonRoute::query()->max('id');

        /** @var array<int, array{dungeonRoute: DungeonRoute, liveSession: LiveSession, killZone: KillZone}> $liveSessions */
        $liveSessions = [];

        try {
            $creatorKillZones = $this->buildRouteCreatorKillZones($logFilePath);
            $creatorEnemies   = $this->toEnemyKeys($creatorKillZones->flatMap(static fn(KillZone $killZone) => $killZone->enemies));

            foreach ([1, self::CHUNK_COUNT] as $chunkCount) {
                $liveSessions[$chunkCount] = $this->createLiveSessionWithKillZone($dungeon->getCurrentMappingVersion(null, null), $creatorKillZones->first()->enemies);
            }

            // Act
            foreach ($liveSessions as $chunkCount => $liveSession) {
                $service = app()->make(LiveSessionBufferProcessingService::class);
                foreach (array_chunk($lines, (int)ceil(count($lines) / $chunkCount)) as $index => $chunk) {
                    $this->storeBatch($liveSession['liveSession'], $chunk, $index + 1);
                    $liveSession['liveSession']->load(['dungeonRoute.mappingVersion.dungeon.floors']);
                    $service->processBuffer($liveSession['liveSession']);
                }
            }

            // Assert
            $this->assertNotEmpty($creatorEnemies, 'The route creator must build a route with enemies from this log');
            foreach ($liveSessions as $chunkCount => $liveSession) {
                $this->assertSame(
                    $creatorEnemies,
                    $this->getKilledOrOverpulledEnemyKeys($liveSession['liveSession']),
                    sprintf('The log fed in %d batch(es) must mark exactly the enemies of the route creator\'s route', $chunkCount),
                );
            }
        } finally {
            foreach ($liveSessions as $liveSession) {
                $this->deleteLiveSession($liveSession);
            }

            foreach (DungeonRoute::query()->where('id', '>', $maxDungeonRouteId)->where('dungeon_id', $dungeon->id)->where('author_id', 1)->get() as $dungeonRoute) {
                $dungeonRoute->delete();
            }

            @unlink($logFilePath);
        }
    }

    /**
     * Pit of Saron is the log the other live session tests use. Brackenhide Hollow's gnolls only resolve through the
     * builder's npc id remapping, and Neltharus has kills whose nearest same-npc enemy is not the one the builder
     * picks.
     *
     * @return array<string, array{string}>
     */
    public static function realCombatLogProvider(): array
    {
        return [
            'pit of saron'       => ['/CombatLogs/mn_s1/WoWCombatLog-061126_213150_10_pit-of-saron_events.txt'],
            'brackenhide hollow' => ['/CombatLogs/df_s2/WoWCombatLog-051323_150800_16_brackenhide-hollow_events.txt'],
            'neltharus'          => ['/CombatLogs/df_s2/WoWCombatLog-051123_172416_16_neltharus_events.txt'],
        ];
    }

    // -------------------------------------------------------------------------
    // Synthetic logs: what the matcher is handed, pass by pass
    // -------------------------------------------------------------------------

    /**
     * Enemy A is engaged on X, enemy B of the same npc right after it, on the same spot. B dies in the first pass and A
     * in the second. The builder hands X to A, being engaged first, and B the other enemy of the group, Y - so B has to
     * resolve to Y in the first pass already, while A is still alive, or its kill would move from X to Y between passes.
     */
    #[Test]
    public function processBuffer_givenAKillEngagedAfterAnEnemyStillInCombat_resolvesTheKillToTheSameEnemyInEveryPass(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        [$mappingVersion, $enemyX, $enemyY] = $this->findTwoEnemiesAloneInTheirGroup();
        $spy                                = $this->createOverpullDetectionSpy();
        $liveSession                        = null;

        try {
            $liveSession = $this->createLiveSessionWithKillZone($mappingVersion, collect());
            $service     = app()->make(LiveSessionBufferProcessingService::class, ['overpullDetectionService' => $spy]);

            $this->storeBatch($liveSession['liveSession'], array_merge($this->logHeader($mappingVersion, $enemyX), [
                $this->engagedLine('00:01:00', $enemyX, 'A00000000A', $enemyX),
                $this->engagedLine('00:01:05', $enemyX, 'B00000000B', $enemyX),
                $this->diedLine('00:01:20', $enemyX, 'B00000000B'),
            ]), 1);

            // Act
            $liveSession['liveSession']->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($liveSession['liveSession']);

            $this->storeBatch($liveSession['liveSession'], [
                $this->engagedLine('00:01:30', $enemyX, 'A00000000A', $enemyX),
                $this->diedLine('00:01:40', $enemyX, 'A00000000A'),
            ], 2);
            $liveSession['liveSession']->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($liveSession['liveSession']);

            // Assert
            $this->assertCount(2, $spy->passes);
            $this->assertSame([$enemyY->id], $spy->passes[0]['kills'], 'B must resolve to Y while A is still alive');
            $this->assertSame([$enemyX->id], $spy->passes[0]['inCombat'], 'A must be in combat on the enemy the matcher hands it');
            $this->assertSame([$enemyY->id, $enemyX->id], $spy->passes[1]['kills'], 'B must still resolve to Y once A has died');
            $this->assertSame([], $spy->passes[1]['inCombat']);
        } finally {
            if ($liveSession !== null) {
                $this->deleteLiveSession($liveSession);
            }
        }
    }

    /**
     * Enemy A was engaged on X and last seen more than the in-combat window before the newest event: it reset. Enemy C
     * is fighting on Y right now, and enemy B is engaged on X after A went quiet and dies. A has left combat, and
     * the enemy it held is free again for B.
     */
    #[Test]
    public function processBuffer_givenAnEnemyNotSeenForLongerThanTheInCombatWindow_neitherReportsItInCombatNorKeepsItsEnemy(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        [$mappingVersion, $enemyX, $enemyY] = $this->findTwoEnemiesAloneInTheirGroup();
        $spy                                = $this->createOverpullDetectionSpy();
        $liveSession                        = null;

        try {
            $liveSession = $this->createLiveSessionWithKillZone($mappingVersion, collect());
            $service     = app()->make(LiveSessionBufferProcessingService::class, ['overpullDetectionService' => $spy]);

            $this->storeBatch($liveSession['liveSession'], array_merge($this->logHeader($mappingVersion, $enemyX), [
                $this->engagedLine('00:01:00', $enemyX, 'A00000000A', $enemyX),
                $this->engagedLine('00:02:00', $enemyY, 'C00000000C', $enemyY),
                $this->engagedLine('00:02:05', $enemyX, 'B00000000B', $enemyX),
                $this->diedLine('00:02:10', $enemyX, 'B00000000B'),
            ]), 1);

            // Act
            $liveSession['liveSession']->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($liveSession['liveSession']);

            // Assert
            $this->assertCount(1, $spy->passes);
            $this->assertSame([$enemyY->id], $spy->passes[0]['inCombat'], 'Only C, seen within the window, is in combat');
            $this->assertSame([$enemyX->id], $spy->passes[0]['kills'], 'B must get X, which aged-out A has released');
        } finally {
            if ($liveSession !== null) {
                $this->deleteLiveSession($liveSession);
            }
        }
    }

    /**
     * Enemy A is engaged at 01:00 and still fighting at 01:55; enemy C is engaged at 02:05. Within the in-combat
     * window of the newest event A is still in combat - also when the log is split right after A's 01:55 sighting,
     * where the reduced checkpoint must carry that sighting into the next pass.
     */
    #[Test]
    public function processBuffer_givenAnEnemySeenLastInAnEarlierBatch_reportsTheSameEnemiesInCombatAsTheWholeLog(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        [$mappingVersion, $enemyX, $enemyY] = $this->findTwoEnemiesAloneInTheirGroup();
        $firstBatch                         = array_merge($this->logHeader($mappingVersion, $enemyX), [
            $this->engagedLine('00:01:00', $enemyX, 'A00000000A', $enemyX),
            $this->engagedLine('00:01:55', $enemyX, 'A00000000A', $enemyX),
        ]);
        $secondBatch = [
            $this->engagedLine('00:02:05', $enemyY, 'C00000000C', $enemyY),
        ];

        /** @var array<string, array{batches: array<int, array<int, string>>, spy: LiveSessionOverpullDetectionServiceInterface&object{passes: array<int, array{kills: array<int, int>, inCombat: array<int, int>}>}, liveSession: array{dungeonRoute: DungeonRoute, liveSession: LiveSession, killZone: KillZone}|null}> $runs */
        $runs = [
            'whole log'   => ['batches' => [array_merge($firstBatch, $secondBatch)], 'spy' => $this->createOverpullDetectionSpy(), 'liveSession' => null],
            'two batches' => ['batches' => [$firstBatch, $secondBatch], 'spy' => $this->createOverpullDetectionSpy(), 'liveSession' => null],
        ];

        try {
            // Act
            foreach ($runs as $name => $run) {
                $runs[$name]['liveSession'] = $liveSession = $this->createLiveSessionWithKillZone($mappingVersion, collect());
                $service                    = app()->make(LiveSessionBufferProcessingService::class, ['overpullDetectionService' => $run['spy']]);
                foreach ($run['batches'] as $index => $batch) {
                    $this->storeBatch($liveSession['liveSession'], $batch, $index + 1);
                    $liveSession['liveSession']->load(['dungeonRoute.mappingVersion.dungeon.floors']);
                    $service->processBuffer($liveSession['liveSession']);
                }
            }

            // Assert
            $wholeLogInCombat = collect(end($runs['whole log']['spy']->passes)['inCombat'])->sort()->values()->all();
            $this->assertCount(2, $wholeLogInCombat, 'A and C must both be in combat at the end of the whole log');
            $this->assertSame(
                $wholeLogInCombat,
                collect(end($runs['two batches']['spy']->passes)['inCombat'])->sort()->values()->all(),
            );
        } finally {
            foreach ($runs as $run) {
                if ($run['liveSession'] !== null) {
                    $this->deleteLiveSession($run['liveSession']);
                }
            }
        }
    }

    /**
     * Enemy A dies after its last sighting, enemy B is still fighting: the checkpoint carries B's latest sighting
     * forward, and nothing of A beyond what the route filter keeps.
     */
    #[Test]
    public function processBuffer_givenAnEnemyThatDiedAfterItsLastSighting_keepsOnlyTheLatestSightingOfTheEnemyStillEngaged(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        [$mappingVersion, $enemyX, $enemyY] = $this->findTwoEnemiesAloneInTheirGroup();
        $liveSession                        = null;
        $deadEnemyLatestSighting            = $this->engagedLine('00:01:30', $enemyX, 'A00000000A', $enemyX);
        $engagedEnemyLatestSighting         = $this->engagedLine('00:01:35', $enemyY, 'B00000000B', $enemyY);

        try {
            $liveSession = $this->createLiveSessionWithKillZone($mappingVersion, collect());
            $service     = app()->make(LiveSessionBufferProcessingService::class, ['overpullDetectionService' => $this->createOverpullDetectionSpy()]);

            $this->storeBatch($liveSession['liveSession'], array_merge($this->logHeader($mappingVersion, $enemyX), [
                $this->engagedLine('00:01:00', $enemyX, 'A00000000A', $enemyX),
                $this->engagedLine('00:01:05', $enemyY, 'B00000000B', $enemyY),
                $deadEnemyLatestSighting,
                $engagedEnemyLatestSighting,
                $this->diedLine('00:01:40', $enemyX, 'A00000000A'),
            ]), 1);

            // Act
            $liveSession['liveSession']->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($liveSession['liveSession']);

            // Assert
            $checkpointLines = $this->getCheckpointLines($liveSession['liveSession']);
            $this->assertContains($engagedEnemyLatestSighting, $checkpointLines);
            $this->assertNotContains($deadEnemyLatestSighting, $checkpointLines);
        } finally {
            if ($liveSession !== null) {
                $this->deleteLiveSession($liveSession);
            }
        }
    }

    /**
     * Enemy A keeps fighting over three passes. Each checkpoint holds its first sighting and only its newest one, in
     * log order - before enemy B's first sighting that followed it in the first pass.
     */
    #[Test]
    public function processBuffer_givenAnEnemySeenInThreePasses_keepsOnlyItsNewestSightingInLogOrder(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        [$mappingVersion, $enemyX, $enemyY] = $this->findTwoEnemiesAloneInTheirGroup();
        $liveSession                        = null;
        $header                             = $this->logHeader($mappingVersion, $enemyX);
        $firstSightingA                     = $this->engagedLine('00:01:00', $enemyX, 'A00000000A', $enemyX);
        $firstSightingB                     = $this->engagedLine('00:01:20', $enemyY, 'B00000000B', $enemyY);
        $sightingsA                         = [
            $this->engagedLine('00:01:10', $enemyX, 'A00000000A', $enemyX),
            $this->engagedLine('00:01:30', $enemyX, 'A00000000A', $enemyX),
            $this->engagedLine('00:01:40', $enemyX, 'A00000000A', $enemyX),
        ];
        $batches = [
            array_merge($header, [$firstSightingA, $sightingsA[0], $firstSightingB]),
            [$sightingsA[1]],
            [$sightingsA[2]],
        ];

        /** @var array<int, array<int, string>> $checkpoints */
        $checkpoints = [];

        try {
            $liveSession = $this->createLiveSessionWithKillZone($mappingVersion, collect());
            $service     = app()->make(LiveSessionBufferProcessingService::class, ['overpullDetectionService' => $this->createOverpullDetectionSpy()]);

            // Act
            foreach ($batches as $index => $batch) {
                $this->storeBatch($liveSession['liveSession'], $batch, $index + 1);
                $liveSession['liveSession']->load(['dungeonRoute.mappingVersion.dungeon.floors']);
                $service->processBuffer($liveSession['liveSession']);
                $checkpoints[] = $this->getCheckpointLines($liveSession['liveSession']);
            }

            // Assert
            $this->assertSame(array_merge($header, [$firstSightingA, $sightingsA[0], $firstSightingB]), $checkpoints[0]);
            $this->assertSame(array_merge($header, [$firstSightingA, $firstSightingB, $sightingsA[1]]), $checkpoints[1]);
            $this->assertSame(array_merge($header, [$firstSightingA, $firstSightingB, $sightingsA[2]]), $checkpoints[2]);
        } finally {
            if ($liveSession !== null) {
                $this->deleteLiveSession($liveSession);
            }
        }
    }

    /**
     * The Static Anomalies despawn instead of dying, so no death of theirs is ever logged: the builder's rule awards
     * all six off Galvazzt's death, and the live session has to mark them killed along with him.
     */
    #[Test]
    public function processBuffer_givenAKillThatARuleAwardsOtherKillsFor_reportsTheAwardedKillsToo(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::query()->where('key', DungeonKey::TEMPLE_OF_SETHRALISS->value)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion(null, null)->load('dungeon');
        $spy            = $this->createOverpullDetectionSpy();
        $liveSession    = null;
        $galvazztGuid   = sprintf('Creature-0-2085-%d-1001-%d-000008A185', $dungeon->map_id, NpcId::GALVAZZT_RESTORED->value);

        try {
            $liveSession = $this->createLiveSessionWithKillZone($mappingVersion, collect());
            $service     = app()->make(LiveSessionBufferProcessingService::class, ['overpullDetectionService' => $spy]);

            $this->storeBatch($liveSession['liveSession'], [
                $this->logLine('00:00:00', 'COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1'),
                $this->logLine('00:00:01', sprintf('MAP_CHANGE,1038,"%s",0.000000,0.000000,0.000000,0.000000', __($dungeon->name, [], 'en_US'))),
                $this->logLine('00:00:02', sprintf('CHALLENGE_MODE_START,"%s",%d,%d,10,[10]', __($dungeon->name, [], 'en_US'), $dungeon->map_id, $dungeon->challenge_mode_id)),
                $this->logLine('22:35:41', $this->spellDamageEvent($galvazztGuid, NpcId::GALVAZZT_RESTORED->value, new IngameXY(-3406.31, 3706.55), 1038)),
                $this->logLine('22:38:04', $this->unitDiedEvent($galvazztGuid, NpcId::GALVAZZT_RESTORED->value)),
            ], 1);

            // Act
            $liveSession['liveSession']->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($liveSession['liveSession']);

            // Assert
            $this->assertCount(1, $spy->passes);
            $killedNpcIds = Enemy::query()->whereIn('id', $spy->passes[0]['kills'])->pluck('npc_id')->countBy();
            $this->assertSame(1, $killedNpcIds->get(NpcId::GALVAZZT_RESTORED->value), 'Galvazzt himself must be killed');
            $this->assertSame(6, $killedNpcIds->get(NpcId::STATIC_ANOMALY->value), 'All six Static Anomalies must be awarded');
        } finally {
            if ($liveSession !== null) {
                $this->deleteLiveSession($liveSession);
            }
        }
    }

    // -------------------------------------------------------------------------

    /**
     * The events fixtures of older logs were cut without their COMBAT_LOG_VERSION line, which decides how every other
     * line parses - it is taken from the full log the fixture was cut from.
     *
     * @return array<int, string>
     */
    private function readCombatLogFixture(string $eventsFile): array
    {
        $lines = file(base_path('tests') . $eventsFile, FILE_IGNORE_NEW_LINES);
        if (str_contains($lines[0], 'COMBAT_LOG_VERSION')) {
            return $lines;
        }

        $zip = new ZipArchive();
        $this->assertTrue($zip->open(base_path('tests') . str_replace('_events.txt', '.zip', $eventsFile)));

        try {
            $stream = $zip->getStream($zip->getNameIndex(0));
            $this->assertNotFalse($stream);
            $versionLine = rtrim(fgets($stream), "\r\n");
            fclose($stream);
        } finally {
            $zip->close();
        }

        $this->assertStringContainsString('COMBAT_LOG_VERSION', $versionLine);

        return array_merge([$versionLine], $lines);
    }

    /**
     * @param array<int, string> $lines
     */
    private function findDungeonForCombatLog(array $lines): Dungeon
    {
        $this->assertSame(1, preg_match('/CHALLENGE_MODE_START,"[^"]*",\d+,(\d+)/', implode("\n", $lines), $matches));

        return Dungeon::query()->where('challenge_mode_id', (int)$matches[1])->firstOrFail();
    }

    /**
     * @return Collection<int, KillZone>
     */
    private function buildRouteCreatorKillZones(string $logFilePath): Collection
    {
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = app(ResultEventDungeonRouteServiceInterface::class)->convertCombatLogToDungeonRoutes($logFilePath)->first();

        return DungeonRoute::query()->whereKey($dungeonRoute->id)->firstOrFail()->killZones()->with('enemies')->get();
    }

    /**
     * Two enemies of one npc that are the only two of that npc in their pack group, on one floor. Engaging one puts
     * the group in combat, so the next engagement of that npc prefers the other one wherever it happens.
     *
     * @return array{MappingVersion, Enemy, Enemy}
     */
    private function findTwoEnemiesAloneInTheirGroup(): array
    {
        $dungeon        = Dungeon::query()->where('key', DungeonKey::PIT_OF_SARON->value)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion(null, null)->load('dungeon');
        $validNpcIds    = app(NpcRepositoryInterface::class)->getInUseNpcIds($mappingVersion);

        $pair = Enemy::query()
            ->with(['enemyPack', 'npc', 'floor.dungeon'])
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('enemy_pack_id')
            ->whereNull('teeming')
            ->whereNull('seasonal_type')
            ->get()
            ->filter(static fn(Enemy $enemy): bool => $validNpcIds->contains($enemy->npc_id) && !$enemy->npc->isBoss())
            ->groupBy(static fn(Enemy $enemy): string => sprintf('%d-%d', $enemy->enemyPack->group, $enemy->npc_id))
            ->first(static fn(Collection $enemies): bool => $enemies->count() === 2 && $enemies->pluck('floor_id')->unique()->count() === 1);

        $this->assertNotNull($pair, 'Pit of Saron must have a pack group holding exactly two enemies of one npc');

        return [$mappingVersion, $pair->first(), $pair->last()];
    }

    /**
     * @param  Collection<int, Enemy>                                                          $killZoneEnemies
     * @return array{dungeonRoute: DungeonRoute, liveSession: LiveSession, killZone: KillZone}
     */
    private function createLiveSessionWithKillZone(MappingVersion $mappingVersion, Collection $killZoneEnemies): array
    {
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        /** @var KillZone $killZone */
        $killZone = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 1]);
        foreach ($killZoneEnemies as $enemy) {
            KillZoneEnemy::query()->create([
                'kill_zone_id' => $killZone->id,
                'npc_id'       => $enemy->npc_id,
                'mdt_id'       => $enemy->mdt_id,
                'enemy_id'     => $enemy->id,
            ]);
        }

        return ['dungeonRoute' => $dungeonRoute, 'liveSession' => $liveSession, 'killZone' => $killZone];
    }

    /**
     * @param array{dungeonRoute: DungeonRoute, liveSession: LiveSession, killZone: KillZone} $liveSession
     */
    private function deleteLiveSession(array $liveSession): void
    {
        $liveSessionId = $liveSession['liveSession']->id;

        LiveSessionEnemyState::query()->where('live_session_id', $liveSessionId)->delete();
        LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSessionId)->delete();
        OverpulledEnemy::query()->where('live_session_id', $liveSessionId)->delete();
        LiveSessionPlayerPosition::query()->where('live_session_id', $liveSessionId)->delete();
        LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSessionId)->delete();
        LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSessionId)->delete();
        KillZoneEnemy::query()->where('kill_zone_id', $liveSession['killZone']->id)->delete();
        KillZone::query()->where('id', $liveSession['killZone']->id)->delete();

        $liveSession['liveSession']->delete();
        $liveSession['dungeonRoute']->delete();
    }

    /**
     * @param array<int, string> $lines
     */
    private function storeBatch(LiveSession $liveSession, array $lines, int $sequence): void
    {
        LiveSessionCombatLogBatch::factory()->withLines($lines)->create([
            'live_session_id' => $liveSession->id,
            'sequence'        => $sequence,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function getCheckpointLines(LiveSession $liveSession): array
    {
        $checkpoint = LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->firstOrFail();

        return explode("\n", gzdecode($checkpoint->buffer));
    }

    /**
     * @param  Collection<int, Enemy> $enemies
     * @return array<int, string>     npc_id:mdt_id, the pair a live session stores its enemy state under
     */
    private function toEnemyKeys(Collection $enemies): array
    {
        return $enemies
            ->map(static fn(Enemy $enemy): string => sprintf('%d:%d', $enemy->npc_id, $enemy->mdt_id))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function getKilledOrOverpulledEnemyKeys(LiveSession $liveSession): array
    {
        return LiveSessionEnemyState::query()
            ->where('live_session_id', $liveSession->id)
            ->where('state', LiveSessionEnemyStateType::Killed)
            ->get(['npc_id', 'mdt_id'])
            ->concat(LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->get(['npc_id', 'mdt_id']))
            ->map(static fn($state): string => sprintf('%d:%d', $state->npc_id, $state->mdt_id))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return LiveSessionOverpullDetectionServiceInterface&object{passes: array<int, array{kills: array<int, int>, inCombat: array<int, int>}>}
     */
    private function createOverpullDetectionSpy(): LiveSessionOverpullDetectionServiceInterface
    {
        return new class implements LiveSessionOverpullDetectionServiceInterface {
            /** @var array<int, array{kills: array<int, int>, inCombat: array<int, int>}> */
            public array $passes = [];

            public function processResolvedKills(LiveSession $liveSession, Collection $resolvedKillsInOrder, Collection $inCombatEnemies): void
            {
                $this->passes[] = [
                    'kills'    => $resolvedKillsInOrder->map(static fn(Enemy $enemy): int => $enemy->id)->values()->all(),
                    'inCombat' => $inCombatEnemies->map(static fn(Enemy $enemy): int => $enemy->id)->values()->all(),
                ];
            }
        };
    }

    /**
     * @return array<int, string>
     */
    private function logHeader(MappingVersion $mappingVersion, Enemy $enemyOnFloor): array
    {
        $dungeon = $mappingVersion->dungeon;

        return [
            $this->logLine('00:00:00', 'COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1'),
            $this->logLine('00:00:01', sprintf(
                'MAP_CHANGE,%d,"%s",0.000000,0.000000,0.000000,0.000000',
                $enemyOnFloor->floor->ui_map_id,
                __($dungeon->name, [], 'en_US'),
            )),
            $this->logLine('00:00:02', sprintf(
                'CHALLENGE_MODE_START,"%s",%d,%d,10,[10]',
                __($dungeon->name, [], 'en_US'),
                $dungeon->map_id,
                $dungeon->challenge_mode_id,
            )),
        ];
    }

    /**
     * A player hitting a creature of $npcEnemy's npc, standing exactly where $location is mapped.
     */
    private function engagedLine(string $time, Enemy $npcEnemy, string $spawnUid, Enemy $location): string
    {
        $ingameXY = app(CoordinatesServiceInterface::class)->calculateIngameLocationForMapLocation(
            new LatLng($location->lat, $location->lng, $location->floor),
        );

        return $this->logLine($time, $this->spellDamageEvent(
            $this->creatureGuid($npcEnemy, $spawnUid),
            $npcEnemy->npc_id,
            $ingameXY,
            $location->floor->ui_map_id,
        ));
    }

    private function diedLine(string $time, Enemy $npcEnemy, string $spawnUid): string
    {
        return $this->logLine($time, $this->unitDiedEvent($this->creatureGuid($npcEnemy, $spawnUid), $npcEnemy->npc_id));
    }

    private function creatureGuid(Enemy $npcEnemy, string $spawnUid): string
    {
        return sprintf('Creature-0-2085-%d-1001-%d-%s', $npcEnemy->floor->dungeon->map_id, $npcEnemy->npc_id, $spawnUid);
    }

    /**
     * Every parameter beyond the GUIDs, the position and the ui map id is filler.
     */
    private function spellDamageEvent(string $creatureGuid, int $npcId, IngameXY $ingameXY, int $uiMapId): string
    {
        return sprintf(
            'SPELL_DAMAGE,%s,"Tester-Realm",0x512,0x2,%s,"Npc %d",0xa48,0x40,31935,"Avenger\'s Shield",0x2,' .
            '%s,0000000000000000,17439357,17490227,0,0,42857,0,0,0,0,7988,7988,0,%s,%s,%d,0.4183,80,' .
            '50870,50870,-1,2,0,0,0,nil,nil,nil',
            self::PLAYER_GUID,
            $creatureGuid,
            $npcId,
            $creatureGuid,
            // AdvancedDataV22 reads the y position first and negates the x position
            $ingameXY->getY(),
            -$ingameXY->getX(),
            $uiMapId,
        );
    }

    private function unitDiedEvent(string $creatureGuid, int $npcId): string
    {
        return sprintf('UNIT_DIED,0000000000000000,nil,0x80000000,0x80000000,%s,"Npc %d",0xa48,0x0,0', $creatureGuid, $npcId);
    }

    private function logLine(string $time, string $rawEvent): string
    {
        return sprintf('%s %s.000  %s', self::LOG_DATE, $time, $rawEvent);
    }
}
