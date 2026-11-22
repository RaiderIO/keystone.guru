<?php

namespace Tests\Feature\Database\Seeders;

use App\Logic\WalkGraph\WalkGrid;
use App\Models\Floor\Floor;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use Database\Seeders\WalkGraphSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class WalkGraphSeederTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int UI_MAP_ID = 9_990_801;

    private const int OTHER_UI_MAP_ID = 9_990_802;

    private const int UNKNOWN_UI_MAP_ID = 9_990_899;

    private string $rootDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['keystoneguru.walk_graph.min_runs' => 15]);
        $this->rootDir = sprintf('%s/walk-graph-seeder-test-%s', sys_get_temp_dir(), uniqid());
        $this->cleanUpTestData();

        /** @var Floor $floor */
        $floor = $this->createDungeon()->floors()->firstOrFail();
        $floor->update(['ui_map_id' => self::UI_MAP_ID]);
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
    public function run_givenNoGraph_insertsSeededGraph(): void
    {
        // Arrange
        $edgeCounts = [WalkGrid::edgeKey(0, 0, 1, 0) => 30, WalkGrid::edgeKey(1, 0, 2, 0) => 12];
        $this->writeWalkGraphFile(self::UI_MAP_ID, 40, $edgeCounts);

        // Act
        new WalkGraphSeeder($this->rootDir)->run();

        // Assert
        $walkGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->first();
        $this->assertNotNull($walkGraph);
        $this->assertSame(WalkGraphSource::Seeder, $walkGraph->source);
        $this->assertSame(40, $walkGraph->run_count);
        $this->assertSame(3, $walkGraph->node_count);
        $this->assertSame(2, $walkGraph->edge_count);
        $this->assertSame(3.0, $walkGraph->cell_size);
        $this->assertSame('2026-11-01 04:00:00', $walkGraph->built_at->toDateTimeString());
        $this->assertSame($edgeCounts, WalkGrid::decodeEdgeCounts($walkGraph->edges));
    }

    #[Test]
    public function run_givenFileWithSeveralEntries_seedsEveryEntry(): void
    {
        // Arrange
        /** @var Floor $otherFloor */
        $otherFloor = $this->createDungeon()->floors()->firstOrFail();
        $otherFloor->update(['ui_map_id' => self::OTHER_UI_MAP_ID]);
        $this->writeWalkGraphsFile([
            $this->walkGraphEntry(self::UI_MAP_ID, 40, [WalkGrid::edgeKey(0, 0, 1, 0) => 40]),
            $this->walkGraphEntry(self::OTHER_UI_MAP_ID, 30, [WalkGrid::edgeKey(2, 2, 3, 2) => 30]),
        ]);

        // Act
        new WalkGraphSeeder($this->rootDir)->run();

        // Assert
        $this->assertSame(
            [self::UI_MAP_ID => 40, self::OTHER_UI_MAP_ID => 30],
            WalkGraph::query()
                ->whereIn('ui_map_id', [self::UI_MAP_ID, self::OTHER_UI_MAP_ID])
                ->orderBy('ui_map_id')
                ->pluck('run_count', 'ui_map_id')
                ->all(),
        );
    }

    #[Test]
    public function run_givenCombatLogGraphWithEnoughRuns_leavesItInPlace(): void
    {
        // Arrange
        $combatLogGraph = WalkGraph::factory()->create(['ui_map_id' => self::UI_MAP_ID, 'run_count' => 15]);
        $this->writeWalkGraphFile(self::UI_MAP_ID, 40, [WalkGrid::edgeKey(5, 5, 6, 5) => 40]);

        // Act
        new WalkGraphSeeder($this->rootDir)->run();

        // Assert
        $walkGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole();
        $this->assertSame(WalkGraphSource::CombatLog, $walkGraph->source);
        $this->assertSame(15, $walkGraph->run_count);
        $this->assertSame($combatLogGraph->edges, $walkGraph->edges);
    }

    #[Test]
    public function run_givenCombatLogGraphWithTooFewRuns_replacesIt(): void
    {
        // Arrange
        $combatLogGraph = WalkGraph::factory()->create(['ui_map_id' => self::UI_MAP_ID, 'run_count' => 14]);
        $edgeCounts     = [WalkGrid::edgeKey(5, 5, 6, 5) => 40];
        $this->writeWalkGraphFile(self::UI_MAP_ID, 40, $edgeCounts);

        // Act
        new WalkGraphSeeder($this->rootDir)->run();

        // Assert
        $walkGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole();
        $this->assertSame($combatLogGraph->id, $walkGraph->id);
        $this->assertSame(WalkGraphSource::Seeder, $walkGraph->source);
        $this->assertSame(40, $walkGraph->run_count);
        $this->assertSame($edgeCounts, WalkGrid::decodeEdgeCounts($walkGraph->edges));
    }

    #[Test]
    public function run_givenSeededGraph_updatesIt(): void
    {
        // Arrange - an older seeded graph, with more runs than the minimum
        $seededGraph = WalkGraph::factory()->seeded()->create(['ui_map_id' => self::UI_MAP_ID, 'run_count' => 30]);
        $edgeCounts  = [WalkGrid::edgeKey(5, 5, 6, 5) => 50];
        $this->writeWalkGraphFile(self::UI_MAP_ID, 50, $edgeCounts);

        // Act
        new WalkGraphSeeder($this->rootDir)->run();

        // Assert
        $walkGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole();
        $this->assertSame($seededGraph->id, $walkGraph->id);
        $this->assertSame(WalkGraphSource::Seeder, $walkGraph->source);
        $this->assertSame(50, $walkGraph->run_count);
        $this->assertSame($edgeCounts, WalkGrid::decodeEdgeCounts($walkGraph->edges));
    }

    #[Test]
    public function run_givenFileForUnknownUiMap_skipsWithWarning(): void
    {
        // Arrange - the known ui map's file is the positive control in the same run
        $this->writeWalkGraphFile(self::UNKNOWN_UI_MAP_ID, 40, [WalkGrid::edgeKey(0, 0, 1, 0) => 40]);
        $this->writeWalkGraphFile(self::UI_MAP_ID, 40, [WalkGrid::edgeKey(0, 0, 1, 0) => 40]);
        /** @var Command&MockInterface $command */
        $command = Mockery::mock(Command::class);
        $command->shouldReceive('warn')->once()->with(Mockery::pattern('/no floor uses ui map 9990899/'));
        $command->shouldReceive('info');
        $seeder = new WalkGraphSeeder($this->rootDir);
        $seeder->setCommand($command);

        // Act
        $seeder->run();

        // Assert
        $this->assertFalse(WalkGraph::query()->where('ui_map_id', self::UNKNOWN_UI_MAP_ID)->exists());
        $this->assertTrue(WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->exists());
    }

    #[Test]
    public function writeGraph_givenCombatLogGraphWithEnoughRunsAtWriteTime_leavesItInPlace(): void
    {
        // Arrange - the row a concurrent combat log build left behind, after the seeder decided to write
        $combatLogGraph = WalkGraph::factory()->create(['ui_map_id' => self::UI_MAP_ID, 'run_count' => 15]);
        $attributes     = [
            'source'                       => WalkGraphSource::Seeder->value,
            'cell_size'                    => 3.0,
            'run_count'                    => 40,
            'node_count'                   => 2,
            'edge_count'                   => 1,
            'largest_component_node_count' => 2,
            'edges'                        => WalkGrid::encodeEdgeCounts([WalkGrid::edgeKey(5, 5, 6, 5) => 40]),
            'built_at'                     => '2026-11-01 04:00:00',
            'updated_at'                   => '2026-11-18 12:00:00',
        ];

        // Act
        $written = new WalkGraphSeeder($this->rootDir)->writeGraph(self::UI_MAP_ID, $attributes, 15);

        // Assert
        $this->assertFalse($written);
        $walkGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole();
        $this->assertSame(WalkGraphSource::CombatLog, $walkGraph->source);
        $this->assertSame(15, $walkGraph->run_count);
        $this->assertSame($combatLogGraph->edges, $walkGraph->edges);
    }

    #[Test]
    public function isWalkGraphFile_givenFileNames_matchesOnlyWalkGraphFiles(): void
    {
        // Arrange
        $walkGraphFile = '/data/midnight/the_blinding_vale/walk_graphs.json';
        $otherFile     = '/data/midnight/the_blinding_vale/walk_graph_2500.json';

        // Act
        $isWalkGraphFile = WalkGraphSeeder::isWalkGraphFile($walkGraphFile);
        $isOtherFile     = WalkGraphSeeder::isWalkGraphFile($otherFile);

        // Assert
        $this->assertTrue($isWalkGraphFile);
        $this->assertFalse($isOtherFile);
    }

    /**
     * Writes a dungeon directory of its own holding just this ui map's graph.
     *
     * @param array<int, int> $edgeCounts
     */
    private function writeWalkGraphFile(int $uiMapId, int $runCount, array $edgeCounts): void
    {
        $this->writeWalkGraphsFile([$this->walkGraphEntry($uiMapId, $runCount, $edgeCounts)], sprintf('test_dungeon_%d', $uiMapId));
    }

    /**
     * @param list<array<string, mixed>> $entries
     */
    private function writeWalkGraphsFile(array $entries, string $dungeonKey = 'test_dungeon'): void
    {
        $dungeonDir = sprintf('%s/test_expansion/%s', $this->rootDir, $dungeonKey);
        File::ensureDirectoryExists($dungeonDir);

        File::put(sprintf('%s/%s', $dungeonDir, WalkGraphSeeder::FILE_NAME), json_encode($entries, JSON_PRETTY_PRINT));
    }

    /**
     * @param  array<int, int>      $edgeCounts
     * @return array<string, mixed>
     */
    private function walkGraphEntry(int $uiMapId, int $runCount, array $edgeCounts): array
    {
        // Every fixture is a single path, so it has one node more than it has edges
        $nodeCount = count($edgeCounts) + 1;

        return [
            'ui_map_id'                    => $uiMapId,
            'cell_size'                    => 3.0,
            'run_count'                    => $runCount,
            'node_count'                   => $nodeCount,
            'edge_count'                   => count($edgeCounts),
            'largest_component_node_count' => $nodeCount,
            'built_at'                     => '2026-11-01T04:00:00+00:00',
            'edges'                        => base64_encode(WalkGrid::encodeEdgeCounts($edgeCounts)),
        ];
    }

    private function cleanUpTestData(): void
    {
        WalkGraph::query()->whereIn('ui_map_id', [self::UI_MAP_ID, self::OTHER_UI_MAP_ID, self::UNKNOWN_UI_MAP_ID])->delete();
    }
}
