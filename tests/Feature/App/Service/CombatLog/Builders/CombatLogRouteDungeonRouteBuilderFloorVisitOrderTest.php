<?php

namespace Tests\Feature\App\Service\CombatLog\Builders;

use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\Floor\FloorRepository;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Repositories\Stub\DungeonRoute\DungeonRouteAffixGroupRepository;
use App\Repositories\Stub\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Stub\KillZone\KillZoneEnemyRepository;
use App\Repositories\Stub\KillZone\KillZoneRepository;
use App\Repositories\Stub\KillZone\KillZoneSpellRepository;
use App\Service\CombatLog\Builders\CombatLogRouteDungeonRouteBuilder;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\PublicTestCase;
use Throwable;

/**
 * Magisters' Terrace (Midnight) sends the party back through the Grand Magister's Asylum (ui_map_id 2516) and the
 * Magisters' Terrace (2511) in a Void phase, mapped as floors of their own that share those ui_map_ids. The Void
 * floors and the tower floors above them also share npc_ids at overlapping ingame X/Y. The fixtures are real runs,
 * anonymised and stripped of spells and player deaths.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilder')]
final class CombatLogRouteDungeonRouteBuilderFloorVisitOrderTest extends PublicTestCase
{
    use LoadsJsonFiles;

    private const string FIXTURE_VOID_PHASE = 'Midnight/midnight_s1_magisters_terrace_20_void_phase';

    private const string FIXTURE_VOID_ASYLUM = 'Midnight/midnight_s1_magisters_terrace_22_void_asylum';

    private const string FIXTURE_ROOT_PATH = '../../../../Controller/Api/V1/APICombatLogController/';

    private const int UI_MAP_ID_MAGISTERS_TERRACE = 2511;

    private const int UI_MAP_ID_GRAND_MAGISTER_ASYLUM = 2516;

    private const int UI_MAP_ID_MECHAGON = 1490;

    private const string FLOOR_NAME_MAGISTERS_TERRACE_VOID = 'dungeons.midnight.magisters_terrace_midnight.floors.magisters_terrace_void';

    #[Test]
    #[DataProvider('magistersTerraceRunProvider')]
    public function build_givenTerraceRevisitedInTheVoidPhase_resolvesThoseKillsOntoTheVoidTerrace(string $fixtureName): void
    {
        // Arrange
        $voidTerraceFloor = Floor::query()->where('name', self::FLOOR_NAME_MAGISTERS_TERRACE_VOID)->firstOrFail();
        $combatLogRoute   = $this->createCombatLogRoute($fixtureName);

        // Act
        $this->createBuilder($combatLogRoute)->build();

        // Assert
        $voidPhaseTerraceNpcs = $this->getResolvedNpcsInEngagedOrder($combatLogRoute)
            ->skipUntil(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->coord->uiMapId === self::UI_MAP_ID_GRAND_MAGISTER_ASYLUM)
            ->filter(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->coord->uiMapId === self::UI_MAP_ID_MAGISTERS_TERRACE);

        $this->assertGreaterThan(20, $voidPhaseTerraceNpcs->count(), 'Precondition: the fixture kills its way back through the terrace in the Void phase');

        $npcsOffTheVoidTerrace = $voidPhaseTerraceNpcs
            ->filter(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getResolvedEnemy()->floor_id !== $voidTerraceFloor->id)
            ->map(fn(CombatLogRouteNpcRequestDto $npc) => $this->describeResolution($npc))
            ->values()
            ->all();

        $this->assertSame([], $npcsOffTheVoidTerrace);
    }

    #[Test]
    #[DataProvider('magistersTerraceRunProvider')]
    public function build_givenStackedFloorsSharingNpcIds_resolvesEveryKillOntoAFloorOfItsLoggedUiMapId(string $fixtureName): void
    {
        // Arrange
        $combatLogRoute = $this->createCombatLogRoute($fixtureName);

        // Act
        $this->createBuilder($combatLogRoute)->build();

        // Assert
        $resolvedNpcs = $this->getResolvedNpcsInEngagedOrder($combatLogRoute);

        $this->assertGreaterThan(150, $resolvedNpcs->count(), 'Precondition: most of the fixture\'s kills resolve to an enemy');

        $npcsOnAnotherUiMapId = $resolvedNpcs
            ->filter(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getResolvedEnemy()->floor->ui_map_id !== $npc->coord->uiMapId)
            ->map(fn(CombatLogRouteNpcRequestDto $npc) => $this->describeResolution($npc))
            ->values()
            ->all();

        $this->assertSame([], $npcsOnAnotherUiMapId);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function magistersTerraceRunProvider(): array
    {
        return [
            '+20, Void phase'  => [self::FIXTURE_VOID_PHASE],
            '+22, Void Asylum' => [self::FIXTURE_VOID_ASYLUM],
        ];
    }

    #[Test]
    public function build_givenRunRevisitingUiMapIds_fetchesTheFloorsOfEachUiMapIdOnce(): void
    {
        // Arrange
        $combatLogRoute  = $this->createCombatLogRoute(self::FIXTURE_VOID_PHASE);
        $floorRepository = new class extends FloorRepository {
            /** @var array<int, int> */
            public array $fetchesByUiMapId = [];

            #[\Override]
            public function findAllByUiMapId(int $uiMapId, int $dungeonId): Collection
            {
                $this->fetchesByUiMapId[$uiMapId] = ($this->fetchesByUiMapId[$uiMapId] ?? 0) + 1;

                return parent::findAllByUiMapId($uiMapId, $dungeonId);
            }
        };

        // Act
        $this->createBuilder($combatLogRoute, $floorRepository)->build();

        // Assert
        $this->assertArrayHasKey(self::UI_MAP_ID_MAGISTERS_TERRACE, $floorRepository->fetchesByUiMapId);
        $this->assertSame(
            array_fill_keys(array_keys($floorRepository->fetchesByUiMapId), 1),
            $floorRepository->fetchesByUiMapId,
        );
    }

    #[Test]
    public function build_givenKillsOnTwoFloorsWithoutUiMapId_keepsTheCurrentFloorAndResolvesBoth(): void
    {
        // Arrange
        $dungeon        = Dungeon::query()->where('key', DungeonKey::MECHAGON_JUNKYARD->value)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floors         = $dungeon->floors()->where('facade', false)->get();

        $this->assertNotNull($mappingVersion);
        $this->assertCount(2, $floors, 'Precondition: the dungeon has two floors');
        $this->assertCount(2, $floors->whereNull('ui_map_id'), 'Precondition: neither floor carries a ui_map_id');

        $inUseNpcIds = app(NpcRepositoryInterface::class)->getInUseNpcIds($mappingVersion);
        $enemies     = $floors->map(static fn(Floor $floor) => Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('floor_id', $floor->id)
            ->whereNull('teeming')
            ->whereIn('npc_id', $inUseNpcIds)
            ->firstOrFail());

        $combatLogRoute = CombatLogRouteRequestDto::createFromArray(
            $this->createCombatLogRouteArray($dungeon, $mappingVersion, $enemies),
        );

        // Act
        $thrown = null;

        try {
            $this->createBuilder($combatLogRoute)->build();
        } catch (Throwable $throwable) {
            $thrown = $throwable;
        }

        // Assert
        $this->assertNull($thrown, sprintf('Building the route threw: %s', $thrown?->getMessage()));
        $this->assertSame(
            $enemies->pluck('id')->all(),
            $combatLogRoute->npcs->map(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getResolvedEnemy()?->id)->all(),
        );
    }

    private function createCombatLogRoute(string $fixtureName): CombatLogRouteRequestDto
    {
        return CombatLogRouteRequestDto::createFromArray(
            $this->getJsonData($fixtureName, self::FIXTURE_ROOT_PATH),
        );
    }

    /**
     * Writes nothing: every write-side repository is a stub.
     */
    private function createBuilder(
        CombatLogRouteRequestDto  $combatLogRoute,
        ?FloorRepositoryInterface $floorRepository = null,
    ): CombatLogRouteDungeonRouteBuilder {
        return new CombatLogRouteDungeonRouteBuilder(
            app(SeasonServiceInterface::class),
            app(SeasonAffixGroupServiceInterface::class),
            app(CoordinatesServiceInterface::class),
            new DungeonRouteRepository(),
            new DungeonRouteAffixGroupRepository(),
            new KillZoneRepository(),
            new KillZoneEnemyRepository(),
            new KillZoneSpellRepository(),
            app(EnemyRepositoryInterface::class),
            app(NpcRepositoryInterface::class),
            app(SpellRepositoryInterface::class),
            $floorRepository ?? app(FloorRepositoryInterface::class),
            app(DungeonRepositoryInterface::class),
            $combatLogRoute,
        );
    }

    /**
     * One kill per enemy, logged at its exact ingame location on Mechagon's ui_map_id, a minute apart.
     *
     * @param  Collection<int, Enemy> $enemies
     * @return array<string, mixed>
     */
    private function createCombatLogRouteArray(Dungeon $dungeon, MappingVersion $mappingVersion, Collection $enemies): array
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);
        $start              = Carbon::parse('2026-01-01T12:00:00.000+00:00');

        $npcs = $enemies->values()->map(static function (Enemy $enemy, int $index) use ($coordinatesService, $start) {
            $ingameXY = $coordinatesService->calculateIngameLocationForMapLocation($enemy->getLatLng());

            return [
                'npcId'     => $enemy->npc_id,
                'spawnUid'  => sprintf('00000000%02d', $index),
                'engagedAt' => $start->copy()->addMinutes($index)->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                'diedAt'    => $start->copy()->addMinutes($index)->addSeconds(10)->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                'coord'     => [
                    'x'       => $ingameXY->getX(),
                    'y'       => $ingameXY->getY(),
                    'uiMapId' => self::UI_MAP_ID_MECHAGON,
                ],
            ];
        })->all();

        return [
            'metadata' => [
                'runId'         => 'mechagon-junkyard-floors-without-ui-map-id',
                'keystoneRunId' => 98765,
                'loggedRunId'   => 87654,
                'period'        => 99,
                'season'        => 'season-test',
                'regionId'      => 2,
                'realmType'     => 'live',
                'wowInstanceId' => $dungeon->map_id,
            ],
            'settings' => [
                'temporary'      => true,
                'debugIcons'     => false,
                'mappingVersion' => $mappingVersion->version,
            ],
            'challengeMode' => [
                'start'           => $start->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                'end'             => $start->copy()->addMinutes(30)->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT),
                'success'         => true,
                'durationMs'      => 1800000,
                'parTimeMs'       => 1800000,
                'timerFraction'   => 1,
                'challengeModeId' => $dungeon->challenge_mode_id,
                'level'           => 10,
                'numDeaths'       => 0,
                'affixes'         => null,
            ],
            'roster' => [
                'numMembers'       => 5,
                'averageItemLevel' => 600,
                'characterIds'     => [0, 1, 2, 3, 4],
                'specIds'          => [105, 72, 72, 72, 73],
                'classIds'         => [11, 1, 1, 1, 1],
            ],
            'npcs'         => $npcs,
            'spells'       => [],
            'playerDeaths' => [],
        ];
    }

    /**
     * @return Collection<int, CombatLogRouteNpcRequestDto>
     */
    private function getResolvedNpcsInEngagedOrder(CombatLogRouteRequestDto $combatLogRoute): Collection
    {
        return $combatLogRoute->npcs
            ->sortBy(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getEngagedAt()->getTimestampMs())
            ->filter(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getResolvedEnemy() !== null)
            ->values();
    }

    private function describeResolution(CombatLogRouteNpcRequestDto $npc): string
    {
        $resolvedFloor = $npc->getResolvedEnemy()->floor;

        return sprintf(
            '%s logged on ui_map_id %d resolved onto floor %d (index %d, ui_map_id %d)',
            $npc->getUniqueId(),
            $npc->coord->uiMapId,
            $resolvedFloor->id,
            $resolvedFloor->index,
            $resolvedFloor->ui_map_id,
        );
    }
}
