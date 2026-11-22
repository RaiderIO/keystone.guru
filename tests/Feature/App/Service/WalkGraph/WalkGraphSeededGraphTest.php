<?php

namespace Tests\Feature\App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\CombatLog\CombatLogWalkGraphContribution;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\WalkGraph\WalkGraphServiceInterface;
use Database\Seeders\WalkGraphSeeder;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * A graph loaded by {@see WalkGraphSeeder} going through the daily build: it survives too few combat log runs, and
 * still gets its pack walking distances.
 */
#[Group('WalkGraph')]
final class WalkGraphSeededGraphTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int UI_MAP_ID = 9_990_811;

    private const float CELL_SIZE = 3.0;

    private const int SEEDED_RUN_COUNT = 40;

    private string $rootDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'keystoneguru.walk_graph.cell_size' => self::CELL_SIZE,
            'keystoneguru.walk_graph.min_runs'  => 15,
        ]);
        $this->rootDir = sprintf('%s/walk-graph-seeded-graph-test-%s', sys_get_temp_dir(), uniqid());
        $this->cleanUpTestData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->cleanUpTestData();
            File::deleteDirectory($this->rootDir);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function buildAll_givenSeededGraphAndTooFewCombatLogRuns_keepsTheSeededGraphAndWritesItsPackDistances(): void
    {
        // Arrange - the seeded graph walks a straight corridor between the packs; the one combat log run walked elsewhere
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();

        $seededEdgeCounts = [
            WalkGrid::edgeKey($baseCellX, $baseCellY, $baseCellX + 1, $baseCellY)     => self::SEEDED_RUN_COUNT,
            WalkGrid::edgeKey($baseCellX + 1, $baseCellY, $baseCellX + 2, $baseCellY) => self::SEEDED_RUN_COUNT,
        ];
        $this->writeWalkGraphFile($seededEdgeCounts);
        new WalkGraphSeeder($this->rootDir)->run();
        CombatLogWalkGraphContribution::factory()
            ->withEdgeKeys([WalkGrid::edgeKey($baseCellX + 50, $baseCellY + 50, $baseCellX + 51, $baseCellY + 50)])
            ->create(['ui_map_id' => self::UI_MAP_ID]);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);

        // Act
        app(WalkGraphServiceInterface::class)->buildAll();

        // Assert
        $walkGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole();
        $this->assertSame(WalkGraphSource::Seeder, $walkGraph->source);
        $this->assertSame(self::SEEDED_RUN_COUNT, $walkGraph->run_count);
        $this->assertSame($seededEdgeCounts, WalkGrid::decodeEdgeCounts($walkGraph->edges));
        $distances = EnemyPackWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->get();
        $this->assertCount(1, $distances);
        $this->assertEqualsWithDelta(6.0, $distances->first()->walk_distance, 0.05);
    }

    /**
     * @param array<int, int> $edgeCounts
     */
    private function writeWalkGraphFile(array $edgeCounts): void
    {
        $dungeonDir = sprintf('%s/test_expansion/test_dungeon', $this->rootDir);
        File::ensureDirectoryExists($dungeonDir);

        File::put(sprintf('%s/%s', $dungeonDir, WalkGraphSeeder::FILE_NAME), json_encode([[
            'ui_map_id'                    => self::UI_MAP_ID,
            'cell_size'                    => self::CELL_SIZE,
            'run_count'                    => self::SEEDED_RUN_COUNT,
            'node_count'                   => count($edgeCounts) + 1,
            'edge_count'                   => count($edgeCounts),
            'largest_component_node_count' => count($edgeCounts) + 1,
            'built_at'                     => '2026-11-01T04:00:00+00:00',
            'edges'                        => base64_encode(WalkGrid::encodeEdgeCounts($edgeCounts)),
        ]]));
    }

    /**
     * @return array{Floor, MappingVersion, int, int} the floor, its mapping version and the cell at the floor's centre
     */
    private function createFloor(): array
    {
        $dungeon = $this->createDungeon();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();
        $floor->update(['ui_map_id' => self::UI_MAP_ID]);

        return [
            $floor,
            $dungeon->mappingVersions()->firstOrFail(),
            WalkGrid::cellCoordinate(($floor->ingame_min_x + $floor->ingame_max_x) / 2, self::CELL_SIZE),
            WalkGrid::cellCoordinate(($floor->ingame_min_y + $floor->ingame_max_y) / 2, self::CELL_SIZE),
        ];
    }

    private function createPackAtCell(MappingVersion $mappingVersion, Floor $floor, int $cellX, int $cellY): void
    {
        $enemyPack = EnemyPack::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'group'              => 1,
            'label'              => 'Walk graph seeded graph test pack',
            'teeming'            => null,
            'faction'            => 'any',
            'vertices_json'      => '[]',
        ]);

        $latLng = app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(
            new IngameXY(($cellX + 0.5) * self::CELL_SIZE, ($cellY + 0.5) * self::CELL_SIZE, $floor),
        );

        Enemy::create([
            'mapping_version_id' => $mappingVersion->id,
            'enemy_pack_id'      => $enemyPack->id,
            'floor_id'           => $floor->id,
            'npc_id'             => 0,
            'teeming'            => null,
            'required'           => true,
            'lat'                => $latLng->getLat(),
            'lng'                => $latLng->getLng(),
        ]);
    }

    private function cleanUpTestData(): void
    {
        $mappingVersionIds = MappingVersion::query()
            ->whereIn('dungeon_id', Dungeon::query()->where('key', 'like', 'test_dungeon_%')->select('id'))
            ->pluck('id');

        // Enemy, EnemyPack and MappingVersion are SeederModels - the query builder removes them
        Enemy::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        EnemyPack::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        EnemyPackWalkDistance::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        CombatLogWalkGraphContribution::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
        WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
    }
}
