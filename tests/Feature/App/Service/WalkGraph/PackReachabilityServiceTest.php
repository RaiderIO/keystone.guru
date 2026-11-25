<?php

namespace Tests\Feature\App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\DungeonStart;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcType;
use App\Models\Polyline;
use App\Models\WalkGraph\WalkGraph;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\WalkGraph\PackReachabilityServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
#[Group('PackReachabilityService')]
final class PackReachabilityServiceTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int UI_MAP_ID = 9_990_834;

    private const float CELL_SIZE = 3.0;

    /** A straight corridor of 41 cells along y = 0 */
    private const array CORRIDOR = [[[0, 0], [40, 0]]];

    /** The corridor plus a loop that leaves it at x = 10 and rejoins it at x = 30, ten cells (30 yd) north of it */
    private const array CORRIDOR_WITH_BYPASS = [[[0, 0], [40, 0]], [[10, 0], [10, 10], [30, 10], [30, 0]]];

    private Floor $floor;

    private MappingVersion $mappingVersion;

    private int $baseCellX;

    private int $baseCellY;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'keystoneguru.walk_graph.min_runs'               => 1,
            'keystoneguru.walk_graph.snap_radius'            => 15.0,
            'keystoneguru.walk_graph.max_walk_distance'      => 600.0,
            'keystoneguru.walk_graph.aggro.radius'           => 7.5,
            'keystoneguru.walk_graph.aggro.max_detour_ratio' => 2.5,
            'keystoneguru.walk_graph.aggro.min_detour'       => 10.0,
        ]);
        WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();

        $dungeon = $this->createDungeon();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();
        $floor->update(['ui_map_id' => self::UI_MAP_ID]);

        $this->floor          = $floor;
        $this->mappingVersion = $dungeon->mappingVersions()->firstOrFail();
        $this->baseCellX      = WalkGrid::cellCoordinate(($floor->ingame_min_x + $floor->ingame_max_x) / 2, self::CELL_SIZE);
        $this->baseCellY      = WalkGrid::cellCoordinate(($floor->ingame_min_y + $floor->ingame_max_y) / 2, self::CELL_SIZE);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function getNeighbours_givenPackInTheWay_leavesThePackBehindItOut(): void
    {
        // Arrange
        $this->createWalkGraph(self::CORRIDOR);
        $first  = $this->createPackAtCell(0, 0);
        $middle = $this->createPackAtCell(20, 0);
        $last   = $this->createPackAtCell(40, 0);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame([$middle->id], array_column($neighbours[$first->id], 'enemy_pack_id'));
        $this->assertSame([$first->id, $last->id], array_column($neighbours[$middle->id], 'enemy_pack_id'));
        $this->assertSame([$middle->id], array_column($neighbours[$last->id], 'enemy_pack_id'));
        $this->assertEqualsWithDelta(60.0, $neighbours[$first->id][0]['distance'], 0.1);
        $this->assertFalse($neighbours[$first->id][0]['unverified']);
        $this->assertNull($neighbours[$first->id][0]['detour_ratio']);
    }

    #[Test]
    public function getNeighbours_givenWayAroundThePackInTheWay_returnsTheDetour(): void
    {
        // Arrange
        $this->createWalkGraph(self::CORRIDOR_WITH_BYPASS);
        $first = $this->createPackAtCell(0, 0);
        $this->createPackAtCell(20, 0);
        $last = $this->createPackAtCell(40, 0);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert - 120 yd along the corridor, plus 20 cells (60 yd) up and down the loop around the middle pack
        $toLast = collect($neighbours[$first->id])->firstWhere('enemy_pack_id', $last->id);
        $this->assertNotNull($toLast);
        $this->assertEqualsWithDelta(180.0, $toLast['distance'], 0.1);
        $this->assertSame(1.5, $toLast['detour_ratio']);
        $this->assertFalse($toLast['unverified']);
    }

    #[Test]
    public function getNeighbours_givenDetourLongerThanTheMaxDetourRatio_leavesThePackOut(): void
    {
        // Arrange - the detour is 1.5 times the direct walk
        config(['keystoneguru.walk_graph.aggro.max_detour_ratio' => 1.4]);
        $this->createWalkGraph(self::CORRIDOR_WITH_BYPASS);
        $first  = $this->createPackAtCell(0, 0);
        $middle = $this->createPackAtCell(20, 0);
        $last   = $this->createPackAtCell(40, 0);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame([$middle->id], array_column($neighbours[$first->id], 'enemy_pack_id'));
        $this->assertContains($middle->id, array_column($neighbours[$last->id], 'enemy_pack_id'));
    }

    #[Test]
    public function getNeighbours_givenPatrolPathAcrossTheCorridor_blocksItBetweenItsVertices(): void
    {
        // Arrange - the patrol walks from 30 cells north to 30 cells south of the corridor: no vertex is near it
        $this->createWalkGraph(self::CORRIDOR);
        $first     = $this->createPackAtCell(0, 0);
        $last      = $this->createPackAtCell(40, 0);
        $patroller = $this->createEnemyAtCell(null, 20, 30);

        $neighboursWithoutPatrol = $this->getNeighbours();
        $this->createPatrol($patroller, [[20, 30], [20, -30]]);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame([$last->id], array_column($neighboursWithoutPatrol[$first->id], 'enemy_pack_id'));
        $this->assertSame([], $neighbours[$first->id]);
    }

    #[Test]
    #[DataProvider('conditionalEnemyProvider')]
    public function getNeighbours_givenConditionalEnemyInTheWay_doesNotBlock(string $condition): void
    {
        // Arrange
        $this->createWalkGraph(self::CORRIDOR);
        $first  = $this->createPackAtCell(0, 0);
        $middle = $this->createPackAtCell(20, 0, match ($condition) {
            'teeming' => ['teeming' => 'visible'],
            'faction' => ['faction' => Faction::FACTION_HORDE],
            'critter' => ['npc_id' => Npc::query()->where('npc_type_id', NpcType::CRITTER)->orderBy('id')->value('id')],
        });
        $last = $this->createPackAtCell(40, 0);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame([$middle->id, $last->id], array_column($neighbours[$first->id], 'enemy_pack_id'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function conditionalEnemyProvider(): array
    {
        return [
            'teeming only' => ['teeming'],
            'one faction'  => ['faction'],
            'critter'      => ['critter'],
        ];
    }

    #[Test]
    public function getNeighbours_givenFloorWithoutWalkGraph_returnsUnverifiedStraightLines(): void
    {
        // Arrange
        $first  = $this->createPackAtCell(0, 0);
        $middle = $this->createPackAtCell(20, 0);
        $last   = $this->createPackAtCell(40, 0);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame([$middle->id, $last->id], array_column($neighbours[$first->id], 'enemy_pack_id'));
        $this->assertTrue($neighbours[$first->id][1]['unverified']);
        $this->assertEqualsWithDelta(120.0, $neighbours[$first->id][1]['distance'], 0.1);
    }

    #[Test]
    public function getOpeningPacks_givenDungeonStart_returnsOnlyThePacksReachableFromIt(): void
    {
        // Arrange
        $this->createWalkGraph([[[-10, 0], [40, 0]]]);
        $first = $this->createPackAtCell(0, 0);
        $this->createPackAtCell(20, 0);
        $latLng       = $this->cellLatLng(-10, 0);
        $dungeonStart = DungeonStart::factory()->create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floor->id,
            'lat'                => $latLng->getLat(),
            'lng'                => $latLng->getLng(),
        ]);

        // Act
        $openingPacks = app(PackReachabilityServiceInterface::class)->getOpeningPacks(
            DungeonStart::query()->whereKey($dungeonStart->id)->with('floor')->get(),
            $this->getEnemyPacks(),
            $this->getEnemies(),
            4,
        );

        // Assert
        $this->assertSame([$first->id], array_column($openingPacks[$dungeonStart->id], 'enemy_pack_id'));
        $this->assertEqualsWithDelta(30.0, $openingPacks[$dungeonStart->id][0]['distance'], 0.1);
    }

    #[Test]
    public function getReachablePacksFrom_givenPackInTheWayKilled_returnsThePackBehindIt(): void
    {
        // Arrange - the walk starts at the first pack, which was just killed
        $this->createWalkGraph(self::CORRIDOR);
        $first   = $this->createPackAtCell(0, 0);
        $middle  = $this->createPackAtCell(20, 0);
        $last    = $this->createPackAtCell(40, 0);
        $from    = new IngameXY(($this->baseCellX + 0.5) * self::CELL_SIZE, ($this->baseCellY + 0.5) * self::CELL_SIZE, $this->floor);
        $service = app(PackReachabilityServiceInterface::class);

        // Act
        $withMiddleStanding = $service->getReachablePacksFrom($from, ...$this->getStandingPacksAndEnemies([$first->id]), packCount: 4);
        $withMiddleKilled   = $service->getReachablePacksFrom($from, ...$this->getStandingPacksAndEnemies([$first->id, $middle->id]), packCount: 4);

        // Assert
        $this->assertSame([$middle->id], array_column($withMiddleStanding, 'enemy_pack_id'));
        $this->assertSame([$last->id], array_column($withMiddleKilled, 'enemy_pack_id'));
        $this->assertEqualsWithDelta(120.0, $withMiddleKilled[0]['distance'], 0.1);
    }

    /**
     * @param  list<int>                                                                                              $killedEnemyPackIds
     * @return array{enemyPacks: EloquentCollection<int, EnemyPack>, standingEnemies: EloquentCollection<int, Enemy>}
     */
    private function getStandingPacksAndEnemies(array $killedEnemyPackIds): array
    {
        $standingEnemies = $this->getEnemies()->whereNotIn('enemy_pack_id', $killedEnemyPackIds);
        $enemyPacks      = $this->getEnemyPacks();
        foreach ($enemyPacks as $enemyPack) {
            $enemyPack->setRelation('enemies', $standingEnemies->where('enemy_pack_id', $enemyPack->id)->values());
        }

        return ['enemyPacks' => $enemyPacks, 'standingEnemies' => $standingEnemies];
    }

    /**
     * @return array<int, list<array{enemy_pack_id: int, distance: float, unverified: bool, detour_ratio: float|null}>>
     */
    private function getNeighbours(): array
    {
        return app(PackReachabilityServiceInterface::class)->getNeighbours(
            $this->mappingVersion,
            $this->getEnemyPacks(),
            $this->getEnemies(),
            4,
        );
    }

    /**
     * @return EloquentCollection<int, EnemyPack>
     */
    private function getEnemyPacks(): EloquentCollection
    {
        return $this->mappingVersion->enemyPacks()->with(['enemies', 'floor'])->orderBy('id')->get();
    }

    /**
     * @return EloquentCollection<int, Enemy>
     */
    private function getEnemies(): EloquentCollection
    {
        return $this->mappingVersion->enemies()->with('floor')->orderBy('id')->get();
    }

    /**
     * @param list<list<array{int, int}>> $corridors cell offsets each corridor runs straight between
     */
    private function createWalkGraph(array $corridors): void
    {
        $edgeCounts = [];
        foreach ($corridors as $corners) {
            $cells = [];
            for ($i = 1; $i < count($corners); $i++) {
                [$fromX, $fromY] = $corners[$i - 1];
                [$toX, $toY]     = $corners[$i];
                $steps           = max(abs($toX - $fromX), abs($toY - $fromY));
                for ($step = $i === 1 ? 0 : 1; $step <= $steps; $step++) {
                    $cells[] = [$fromX + intdiv(($toX - $fromX) * $step, $steps), $fromY + intdiv(($toY - $fromY) * $step, $steps)];
                }
            }

            for ($i = 1; $i < count($cells); $i++) {
                $edgeCounts[WalkGrid::edgeKey(
                    $this->baseCellX + $cells[$i - 1][0],
                    $this->baseCellY + $cells[$i - 1][1],
                    $this->baseCellX + $cells[$i][0],
                    $this->baseCellY + $cells[$i][1],
                )] = 1;
            }
        }

        WalkGraph::factory()->create([
            'ui_map_id' => self::UI_MAP_ID,
            'cell_size' => self::CELL_SIZE,
            'run_count' => 1,
            'edges'     => WalkGrid::encodeEdgeCounts($edgeCounts),
        ]);
    }

    /**
     * @param array<string, mixed> $enemyAttributes
     */
    private function createPackAtCell(int $cellX, int $cellY, array $enemyAttributes = []): EnemyPack
    {
        $enemyPack = EnemyPack::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floor->id,
            'group'              => 1,
            'label'              => 'Pack reachability test pack',
            'teeming'            => null,
            'faction'            => 'any',
            'vertices_json'      => '[]',
        ]);

        $this->createEnemyAtCell($enemyPack, $cellX, $cellY, $enemyAttributes);

        return $enemyPack;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createEnemyAtCell(?EnemyPack $enemyPack, int $cellX, int $cellY, array $attributes = []): Enemy
    {
        $latLng = $this->cellLatLng($cellX, $cellY);

        return Enemy::create(array_merge([
            'mapping_version_id' => $this->mappingVersion->id,
            'enemy_pack_id'      => $enemyPack?->id,
            'floor_id'           => $this->floor->id,
            'npc_id'             => 0,
            'teeming'            => null,
            'faction'            => Faction::FACTION_ANY,
            'required'           => true,
            'lat'                => $latLng->getLat(),
            'lng'                => $latLng->getLng(),
        ], $attributes));
    }

    /**
     * @param list<array{int, int}> $vertexCells
     */
    private function createPatrol(Enemy $enemy, array $vertexCells): void
    {
        $enemyPatrol = EnemyPatrol::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floor->id,
            'polyline_id'        => -1,
            'teeming'            => null,
            'faction'            => 'any',
        ]);

        $polyline = Polyline::create([
            'model_id'       => $enemyPatrol->id,
            'model_class'    => EnemyPatrol::class,
            'color'          => '#f00000',
            'color_animated' => null,
            'weight'         => 2,
            'vertices_json'  => json_encode(array_map(function (array $cell) {
                $latLng = $this->cellLatLng($cell[0], $cell[1]);

                return ['lat' => $latLng->getLat(), 'lng' => $latLng->getLng()];
            }, $vertexCells)),
        ]);

        $enemyPatrol->update(['polyline_id' => $polyline->id]);
        $enemy->update(['enemy_patrol_id' => $enemyPatrol->id]);
    }

    private function cellLatLng(int $cellX, int $cellY): LatLng
    {
        return app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(new IngameXY(
            ($this->baseCellX + $cellX + 0.5) * self::CELL_SIZE,
            ($this->baseCellY + $cellY + 0.5) * self::CELL_SIZE,
            $this->floor,
        ));
    }
}
