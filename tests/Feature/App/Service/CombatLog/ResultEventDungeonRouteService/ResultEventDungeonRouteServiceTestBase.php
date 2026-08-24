<?php

namespace Tests\Feature\App\Service\CombatLog\ResultEventDungeonRouteService;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;
use Illuminate\Support\Collection;
use Override;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

/**
 * Regression harness for the route builder that runs on a combat log we parsed ourselves
 * (ResultEventDungeonRouteBuilder), as opposed to the one that runs on what Raider.IO POSTs us
 * (CombatLogRouteDungeonRouteBuilder, covered by the APICombatLogController CombatLogRoute tests).
 *
 * That path had no coverage at all, which is why the per-dungeon DungeonRouteBuilderRules were left inert on it in
 * #4272 - see #4275. The dungeons the rules apply to have no combat log fixture in tests/CombatLogs/ and two of them
 * (The Blinding Vale, Voidscar Arena) are Midnight dungeons for which no real log exists to capture, so each test
 * writes the handful of log lines its scenario needs instead. The lines are the same subset the committed
 * *_events.txt fixtures hold - MAP_CHANGE, CHALLENGE_MODE_START, an advanced SPELL_DAMAGE per engage and a UNIT_DIED
 * per death - so they exercise the real parser and the real result event pipeline, not a stub of either.
 *
 * The npc ids, spawn uids and ingame positions are taken from the equivalent CombatLogRoute test for the same
 * dungeon, so a scenario asserts the same thing through both builders.
 */
#[SlowTest]
abstract class ResultEventDungeonRouteServiceTestBase extends PublicTestCase
{
    /** @var string The month/day every synthetic log line is stamped with - a run's real date is irrelevant here */
    private const string LOG_DATE = '8/21';

    /** @var string The player dealing all the damage in a synthetic log; only its GUID being a player matters */
    private const string PLAYER_GUID = 'Player-4184-00867C26';

    protected Dungeon $dungeon;

    private ResultEventDungeonRouteServiceInterface $resultEventDungeonRouteService;

    /** @var array<int, string> Absolute paths of the synthetic logs written by this test, removed on tearDown() */
    private array $temporaryLogFilePaths = [];

    /** @var array<int, DungeonRoute> The routes the service persisted for this test, removed on tearDown() */
    private array $createdDungeonRoutes = [];

    abstract protected function getDungeonKey(): string;

    /**
     * The ui_map_id of the floor a scenario's kills are logged on, unless it names another one per kill.
     */
    abstract protected function getDefaultUiMapId(): int;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dungeon                        = Dungeon::where('key', $this->getDungeonKey())->firstOrFail();
        $this->resultEventDungeonRouteService = app(ResultEventDungeonRouteServiceInterface::class);
    }

    /**
     * The route is created deep inside the service rather than by the test, so there is nothing to wrap in a
     * try/finally - the test only ever sees it once it already exists. Cleaning up here instead covers a failed
     * assertion and a throwing build alike.
     */
    #[Override]
    protected function tearDown(): void
    {
        foreach ($this->createdDungeonRoutes as $dungeonRoute) {
            $dungeonRoute->delete();
        }

        foreach ($this->temporaryLogFilePaths as $temporaryLogFilePath) {
            if (file_exists($temporaryLogFilePath)) {
                unlink($temporaryLogFilePath);
            }
        }

        $this->createdDungeonRoutes  = [];
        $this->temporaryLogFilePaths = [];

        parent::tearDown();
    }

    /**
     * Runs the scenario's kills through the full combat log parse + build pipeline and hands back the route it
     * produced, with its kill zones and their enemies loaded.
     *
     * @param array<int, array<string, mixed>> $npcKills As produced by npcKill()
     */
    protected function buildDungeonRouteFromNpcKills(array $npcKills, int $keystoneLevel = 10): DungeonRoute
    {
        $logFilePath = $this->writeSyntheticCombatLog($npcKills, $keystoneLevel);

        $dungeonRoutes = $this->resultEventDungeonRouteService->convertCombatLogToDungeonRoutes($logFilePath);

        $this->assertCount(1, $dungeonRoutes, 'The combat log did not produce exactly one dungeon route');

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute                 = $dungeonRoutes->first();
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }

    /**
     * The kill zones as they were persisted, rather than as the builder left them in memory - the relations the
     * builder sets are built from Stub repositories and do not carry the enemies' mapping attributes.
     *
     * @return Collection<int, KillZone>
     */
    protected function getKillZones(DungeonRoute $dungeonRoute): Collection
    {
        return DungeonRoute::whereKey($dungeonRoute->id)
            ->firstOrFail()
            ->killZones()
            ->with('enemies')
            ->get();
    }

    /**
     * mdt_id (together with npc_id) is what stays stable across mapping versions - the enemy_id itself is reassigned
     * every time the mapping is edited, since MappingVersion clones every enemy on save.
     */
    protected function findResolvedEnemyMdtId(DungeonRoute $dungeonRoute, int $npcId): ?int
    {
        foreach ($this->getKillZones($dungeonRoute) as $killZone) {
            $enemy = $killZone->enemies->firstWhere('npc_id', $npcId);

            if ($enemy !== null) {
                return $enemy->mdt_id;
            }
        }

        return null;
    }

    /**
     * Asserts that a single pull holds all of $npcIds at once - the only way to see a kill a rule awarded, since no
     * death for such an enemy is ever logged.
     *
     * @param array<int, int> $npcIds
     */
    protected function assertNpcIdsInSamePull(DungeonRoute $dungeonRoute, array $npcIds): void
    {
        $matchingPulls = $this->getKillZones($dungeonRoute)->filter(
            static fn(KillZone $killZone): bool => array_diff($npcIds, $killZone->enemies->pluck('npc_id')->all()) === [],
        );

        $this->assertNotEmpty(
            $matchingPulls,
            sprintf('No single pull holds all of NPCs %s', implode(', ', $npcIds)),
        );
    }

    /**
     * One enemy's whole life as the builder needs to see it: engaged somewhere, then dead.
     *
     * @param  string               $spawnUid  Must be unique per enemy - it is what makes the creature GUID unique
     * @param  string               $engagedAt H:i:s on the synthetic log's date
     * @param  string               $diedAt    H:i:s on the synthetic log's date
     * @param  int|null             $uiMapId   Null for the dungeon's default floor
     * @return array<string, mixed>
     */
    protected function npcKill(
        int    $npcId,
        string $spawnUid,
        string $engagedAt,
        string $diedAt,
        float  $x,
        float  $y,
        ?int   $uiMapId = null,
    ): array {
        return [
            'npcId'     => $npcId,
            'spawnUid'  => $spawnUid,
            'engagedAt' => $engagedAt,
            'diedAt'    => $diedAt,
            'x'         => $x,
            'y'         => $y,
            'uiMapId'   => $uiMapId ?? $this->getDefaultUiMapId(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $npcKills
     *
     * @return string The absolute path of the log, named *_events.txt because that is what the pipeline expects
     */
    private function writeSyntheticCombatLog(array $npcKills, int $keystoneLevel): string
    {
        $firstUiMapId = $npcKills === [] ? $this->getDefaultUiMapId() : $npcKills[0]['uiMapId'];

        $lines = [
            // ZONE_CHANGE is deliberately absent: DungeonRouteFilter creates a route off it *and* off
            // CHALLENGE_MODE_START, so including both leaves an orphaned route behind on every run
            $this->logLine('00:00:00', 'COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1'),
            $this->logLine('00:00:01', sprintf(
                'MAP_CHANGE,%d,"%s",0.000000,0.000000,0.000000,0.000000',
                $firstUiMapId,
                __($this->dungeon->name, [], 'en_US'),
            )),
            $this->logLine('00:00:02', sprintf(
                'CHALLENGE_MODE_START,"%s",%d,%d,%d,[10]',
                __($this->dungeon->name, [], 'en_US'),
                $this->dungeon->map_id,
                $this->dungeon->challenge_mode_id,
                $keystoneLevel,
            )),
        ];

        // The builder walks the events in the order the log holds them, so engages and deaths have to be interleaved
        // by timestamp exactly as they would be in a real log
        $events = [];
        foreach ($npcKills as $npcKill) {
            $events[] = ['at' => $npcKill['engagedAt'], 'type' => 'engaged', 'npc' => $npcKill];
            $events[] = ['at' => $npcKill['diedAt'], 'type' => 'died', 'npc' => $npcKill];
        }

        usort($events, static fn(array $a, array $b): int => [$a['at'], $a['type']] <=> [$b['at'], $b['type']]);

        $currentUiMapId = $firstUiMapId;
        foreach ($events as $event) {
            $npcKill = $event['npc'];

            if ($npcKill['uiMapId'] !== $currentUiMapId) {
                $currentUiMapId = $npcKill['uiMapId'];

                $lines[] = $this->logLine($event['at'], sprintf(
                    'MAP_CHANGE,%d,"%s",0.000000,0.000000,0.000000,0.000000',
                    $currentUiMapId,
                    __($this->dungeon->name, [], 'en_US'),
                ));
            }

            $lines[] = $event['type'] === 'engaged'
                ? $this->logLine($event['at'], $this->spellDamageEvent($npcKill))
                : $this->logLine($event['at'], $this->unitDiedEvent($npcKill));
        }

        $lines[] = $this->logLine('23:59:59', sprintf(
            'CHALLENGE_MODE_END,%d,1,%d,1200000,100.000000,100.000000',
            $this->dungeon->map_id,
            $keystoneLevel,
        ));

        // The pipeline reads the log off disk, and only accepts a path ending in _events.txt
        $logFilePath                   = storage_path(sprintf('app/%s_events.txt', uniqid('test-combatlog-', true)));
        $this->temporaryLogFilePaths[] = $logFilePath;

        file_put_contents($logFilePath, implode("\n", $lines) . "\n");

        return $logFilePath;
    }

    /**
     * The event that puts an enemy in combat, and the only one carrying its ingame position. Every parameter beyond
     * the GUID, the position and the ui map id is filler - nothing downstream of the builder reads them.
     *
     * @param array<string, mixed> $npcKill
     */
    private function spellDamageEvent(array $npcKill): string
    {
        $creatureGuid = $this->creatureGuid($npcKill);

        return sprintf(
            // source, dest, spell, then the 19 advanced parameters and the damage suffix
            'SPELL_DAMAGE,%s,"Tester-Realm",0x512,0x2,%s,"Npc %d",0xa48,0x40,31935,"Avenger\'s Shield",0x2,' .
            '%s,0000000000000000,17439357,17490227,0,0,42857,0,0,0,0,7988,7988,0,%s,%s,%d,0.4183,80,' .
            '50870,50870,-1,2,0,0,0,nil,nil,nil',
            self::PLAYER_GUID,
            $creatureGuid,
            $npcKill['npcId'],
            $creatureGuid,
            // AdvancedData reads positionY first and negates positionX - see AdvancedDataV22::setParameters()
            $npcKill['y'],
            -$npcKill['x'],
            $npcKill['uiMapId'],
        );
    }

    /**
     * @param array<string, mixed> $npcKill
     */
    private function unitDiedEvent(array $npcKill): string
    {
        return sprintf(
            'UNIT_DIED,0000000000000000,nil,0x80000000,0x80000000,%s,"Npc %d",0xa48,0x0,0',
            $this->creatureGuid($npcKill),
            $npcKill['npcId'],
        );
    }

    /**
     * @param array<string, mixed> $npcKill
     */
    private function creatureGuid(array $npcKill): string
    {
        return sprintf(
            'Creature-0-2085-%d-1001-%d-%s',
            $this->dungeon->map_id,
            $npcKill['npcId'],
            $npcKill['spawnUid'],
        );
    }

    private function logLine(string $time, string $rawEvent): string
    {
        // Two spaces between the timestamp and the event is what the parser's regex keys off
        return sprintf('%s %s.000  %s', self::LOG_DATE, $time, $rawEvent);
    }
}
