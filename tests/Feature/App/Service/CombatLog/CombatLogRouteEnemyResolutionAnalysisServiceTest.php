<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Logic\Structs\IngameXY;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogRouteEnemyResolutionAnalysisServiceInterface;
use App\Service\CombatLog\Dtos\EnemyResolutionAnalysis\EnemyResolutionGroup;
use App\Service\CombatLog\Dtos\EnemyResolutionAnalysis\EnemyResolutionVerdict;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * Every test works on its own MappingVersion (no enemies unless the test creates them) of a dungeon with ingame
 * coordinates, with enemies and engagements placed by ingame coordinates around the middle of the floor so distances
 * are what the test says they are.
 */
#[Group('CombatLog')]
#[Group('CombatLogRouteEnemyResolutionAnalysisService')]
final class CombatLogRouteEnemyResolutionAnalysisServiceTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const int NPC_ID = 99951;

    private CombatLogRouteEnemyResolutionAnalysisServiceInterface $service;

    private CoordinatesServiceInterface $coordinatesService;

    private Dungeon $dungeon;

    private MappingVersion $mappingVersion;

    private Floor $floor;

    private float $centerX;

    private float $centerY;

    /** @var array<int, int> */
    private array $createdResolutionIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service            = app(CombatLogRouteEnemyResolutionAnalysisServiceInterface::class);
        $this->coordinatesService = app(CoordinatesServiceInterface::class);

        [$this->dungeon] = $this->findDungeon(facadeEnabled: false, constraint: static function (Builder $query): void {
            $query->whereHas('floors', static fn(Builder $floors) => $floors->where('facade', 0)->where('ingame_max_x', '!=', 0));
        });

        $this->floor   = $this->dungeon->floors()->where('facade', 0)->where('ingame_max_x', '!=', 0)->orderBy('index')->firstOrFail();
        $this->centerX = ($this->floor->ingame_min_x + $this->floor->ingame_max_x) / 2;
        $this->centerY = ($this->floor->ingame_min_y + $this->floor->ingame_max_y) / 2;

        $current              = $this->dungeon->getCurrentMappingVersion();
        $this->mappingVersion = MappingVersion::findOrFail(MappingVersion::insertGetId([
            'game_version_id'                 => $current->game_version_id,
            'dungeon_id'                      => $this->dungeon->id,
            'version'                         => $current->version + 100,
            'enemy_forces_required'           => $current->enemy_forces_required,
            'enemy_forces_required_teeming'   => $current->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $current->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $current->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $current->timer_max_seconds,
            'created_at'                      => now(),
            'updated_at'                      => now(),
        ]));
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogRouteEnemyResolution::query()->whereIn('id', $this->createdResolutionIds)->delete();
            // Enemy, EnemyPack and MappingVersion are SeederModels - delete() is refused, use the query builder
            Enemy::query()->where('mapping_version_id', $this->mappingVersion->id)->delete();
            EnemyPack::query()->where('mapping_version_id', $this->mappingVersion->id)->delete();
            MappingVersion::query()->whereKey($this->mappingVersion->id)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function analyze_givenPackEngagedOffsetKeepingItsShape_returnsDisplaced(): void
    {
        // Arrange - three enemies 20 yd apart, every route engages each of them 60 yd further along x
        $enemies = $this->createPack([[0, 0], [20, 0], [0, 20]]);
        foreach (range(1, 10) as $routeId) {
            foreach ($enemies as $enemy) {
                [$x, $y] = $this->ingameXYOf($enemy);
                $this->createResolution($enemy, $x + 60, $y, $routeId);
            }
        }

        // Act
        $groups = $this->analyze();

        // Assert
        $this->assertCount(1, $groups);
        $this->assertSame(EnemyResolutionVerdict::Displaced, $groups[0]->verdict);
        $this->assertSame($enemies[0]->enemy_pack_id, $groups[0]->enemyPackId);
        $this->assertSame(30, $groups[0]->count);
        $this->assertSame(10, $groups[0]->routeCount);
        $this->assertEqualsWithDelta(60, $groups[0]->displacement, 1);
        $this->assertEqualsWithDelta(1.0, $groups[0]->directionConsistency, 0.01);
        $this->assertEqualsWithDelta(1.0, $groups[0]->shapeRatio, 0.05);
        $this->assertFalse($groups[0]->lowVolume);
    }

    #[Test]
    public function analyze_givenPackEngagedBunchedOnOneSpot_returnsConverged(): void
    {
        // Arrange - the same pack, but every member is first seen within a yard of one spot 60 yd away
        $enemies = $this->createPack([[0, 0], [20, 0], [0, 20]]);
        foreach (range(1, 10) as $routeId) {
            foreach ($enemies as $index => $enemy) {
                $this->createResolution($enemy, $this->centerX + 67 + $index * 0.5, $this->centerY + 7, $routeId);
            }
        }

        // Act
        $groups = $this->analyze();

        // Assert
        $this->assertCount(1, $groups);
        $this->assertSame(EnemyResolutionVerdict::Converged, $groups[0]->verdict);
        $this->assertLessThan(0.6, $groups[0]->shapeRatio);
    }

    #[Test]
    public function analyze_givenEngagementsInEveryDirection_returnsScatter(): void
    {
        // Arrange - one enemy without a pack, engaged 60 yd away in four opposite directions
        $enemy = $this->createEnemy(0, 0);
        foreach ([[60, 0], [-60, 0], [0, 60], [0, -60]] as $index => [$offsetX, $offsetY]) {
            foreach (range(1, 3) as $repeat) {
                $this->createResolution($enemy, $this->centerX + $offsetX, $this->centerY + $offsetY, $index * 10 + $repeat);
            }
        }

        // Act
        $groups = $this->analyze();

        // Assert
        $this->assertCount(1, $groups);
        $this->assertSame(EnemyResolutionVerdict::Scatter, $groups[0]->verdict);
        $this->assertLessThan(0.1, $groups[0]->directionConsistency);
    }

    #[Test]
    public function analyze_givenEnemyWithoutPack_groupsItOnItsOwnWithUnknownShape(): void
    {
        // Arrange
        $enemy = $this->createEnemy(0, 0);
        foreach (range(1, 6) as $routeId) {
            $this->createResolution($enemy, $this->centerX + 50, $this->centerY, $routeId);
        }

        // Act
        $groups = $this->analyze();

        // Assert - a single enemy cannot say whether it bunched up, so it is judged on direction alone
        $this->assertCount(1, $groups);
        $this->assertNull($groups[0]->enemyPackId);
        $this->assertSame([$enemy->id], $groups[0]->enemyIds);
        $this->assertNull($groups[0]->shapeRatio);
        $this->assertSame(EnemyResolutionVerdict::Displaced, $groups[0]->verdict);
        $this->assertStringContainsString(sprintf('enemy %d', $enemy->id), $groups[0]->suggestion);
        $this->assertStringContainsString('Too few of its enemies', $groups[0]->suggestion);
    }

    #[Test]
    public function analyze_givenFewRoutes_flagsLowVolumeAndSortsItLast(): void
    {
        // Arrange - one enemy seen in 2 routes, another in 10
        $rare   = $this->createEnemy(0, 0);
        $common = $this->createEnemy(200, 0);
        foreach (range(1, 2) as $routeId) {
            $this->createResolution($rare, $this->centerX + 50, $this->centerY, $routeId);
        }
        foreach (range(11, 20) as $routeId) {
            $this->createResolution($common, $this->centerX + 250, $this->centerY, $routeId);
        }

        // Act
        $groups = $this->analyze();

        // Assert
        $this->assertCount(2, $groups);
        $this->assertSame([$common->id], $groups[0]->enemyIds);
        $this->assertFalse($groups[0]->lowVolume);
        $this->assertSame([$rare->id], $groups[1]->enemyIds);
        $this->assertTrue($groups[1]->lowVolume);
    }

    #[Test]
    public function analyze_givenSmallShareOfAllRoutes_flagsLowVolume(): void
    {
        // Arrange - 5 routes clear the route minimum, but of 110 routes that is below the share minimum
        config(['keystoneguru.enemy_resolution_analysis.min_route_share' => 0.05]);
        $rare   = $this->createEnemy(0, 0);
        $common = $this->createEnemy(200, 0);
        foreach (range(1, 5) as $routeId) {
            $this->createResolution($rare, $this->centerX + 50, $this->centerY, $routeId);
        }
        foreach (range(11, 115) as $routeId) {
            $this->createResolution($common, $this->centerX + 250, $this->centerY, $routeId);
        }

        // Act
        $groups = $this->analyze();

        // Assert
        $rareGroup = collect($groups)->first(static fn(EnemyResolutionGroup $group) => $group->enemyIds === [$rare->id]);
        $this->assertSame(5, $rareGroup->routeCount);
        $this->assertEqualsWithDelta(5 / 110, $rareGroup->routeShare, 0.001);
        $this->assertTrue($rareGroup->lowVolume);
    }

    /**
     * An imported row keeps the route id of the deployment it came from, so the same id from two sources is two routes.
     */
    #[Test]
    public function analyze_givenSameRouteIdFromTwoSources_countsTwoRoutes(): void
    {
        // Arrange
        $enemy = $this->createEnemy(0, 0);
        $this->createResolution($enemy, $this->centerX + 50, $this->centerY, 1234);
        $this->createResolution($enemy, $this->centerX + 50, $this->centerY, 1234, 'production');

        // Act
        $groups = $this->analyze();

        // Assert
        $this->assertSame(2, $groups[0]->routeCount);
    }

    #[Test]
    public function analyze_givenMinDistance_leavesCloserResolutionsOut(): void
    {
        // Arrange
        $enemy = $this->createEnemy(0, 0);
        $this->createResolution($enemy, $this->centerX + 45, $this->centerY, 1, null, 45);
        $this->createResolution($enemy, $this->centerX + 90, $this->centerY, 2, null, 90);

        // Act
        $groups = $this->service->analyze($this->dungeon, $this->mappingVersion, [self::NPC_ID], 60)->groups;

        // Assert
        $this->assertCount(1, $groups);
        $this->assertSame(1, $groups[0]->count);
        $this->assertEqualsWithDelta(90, $groups[0]->displacement, 1);
    }

    #[Test]
    public function analyze_givenNoResolutions_returnsNoGroups(): void
    {
        // Act
        $result = $this->service->analyze($this->dungeon, $this->mappingVersion, [self::NPC_ID]);

        // Assert
        $this->assertSame([], $result->groups);
        $this->assertSame(0, $result->routeCount);
    }

    #[Test]
    public function toArray_givenGroup_returnsBothCentroidsAndTheVerdicts(): void
    {
        // Arrange
        $enemy = $this->createEnemy(0, 0);
        foreach (range(1, 6) as $routeId) {
            $this->createResolution($enemy, $this->centerX + 50, $this->centerY, $routeId);
        }

        // Act
        $array = $this->service->analyze($this->dungeon, $this->mappingVersion, [self::NPC_ID])->setUseFacade(false)->toArray();

        // Assert
        $this->assertCount(1, $array['data']);
        $group = $array['data'][0];
        $this->assertSame($this->floor->id, $group['floor_id']);
        $this->assertEqualsWithDelta($enemy->lat, $group['mapped_centroid']['lat'], 0.01);
        $this->assertEqualsWithDelta($enemy->lng, $group['mapped_centroid']['lng'], 0.01);
        $this->assertSame('displaced', $group['verdict']);
        $this->assertSame(6, $group['route_count']);
        $this->assertSame(6, $array['route_count']);
        $this->assertSame(['displaced', 'converged', 'scatter'], array_keys($array['verdicts']));
    }

    /**
     * @return EnemyResolutionGroup[]
     */
    private function analyze(): array
    {
        return $this->service->analyze($this->dungeon, $this->mappingVersion, [self::NPC_ID])->groups;
    }

    /**
     * @param  array<int, array{0: float, 1: float}> $offsets Ingame offsets from the middle of the floor
     * @return Enemy[]
     */
    private function createPack(array $offsets): array
    {
        $enemyPack = EnemyPack::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floor->id,
            'group'              => 7,
            'teeming'            => null,
            'faction'            => 'any',
            'vertices_json'      => '[]',
        ]);

        return array_map(fn(array $offset): Enemy => $this->createEnemy($offset[0], $offset[1], $enemyPack->id), $offsets);
    }

    private function createEnemy(float $offsetX, float $offsetY, ?int $enemyPackId = null): Enemy
    {
        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
            new IngameXY($this->centerX + $offsetX, $this->centerY + $offsetY, $this->floor),
        );

        return Enemy::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'enemy_pack_id'      => $enemyPackId,
            'floor_id'           => $this->floor->id,
            'npc_id'             => self::NPC_ID,
            'teeming'            => null,
            'required'           => true,
            'lat'                => $latLng->getLat(),
            'lng'                => $latLng->getLng(),
        ]);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function ingameXYOf(Enemy $enemy): array
    {
        $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($enemy->getLatLng());

        return [$ingameXY->getX(), $ingameXY->getY()];
    }

    private function createResolution(Enemy $enemy, float $engagedX, float $engagedY, int $routeId, ?string $source = null, float $distance = 60): void
    {
        $engaged = $this->coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($engagedX, $engagedY, $this->floor));

        $this->createdResolutionIds[] = CombatLogRouteEnemyResolution::factory()->create([
            'dungeon_route_id'   => $routeId,
            'source'             => $source,
            'dungeon_id'         => $this->dungeon->id,
            'floor_id'           => $this->floor->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => self::NPC_ID,
            'enemy_id'           => $enemy->id,
            'lat'                => $engaged->getLat(),
            'lng'                => $engaged->getLng(),
            'enemy_lat'          => $enemy->lat,
            'enemy_lng'          => $enemy->lng,
            'distance'           => $distance,
            'weighted_distance'  => $distance,
        ])->id;
    }
}
