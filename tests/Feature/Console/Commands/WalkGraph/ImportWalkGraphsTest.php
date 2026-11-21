<?php

namespace Tests\Feature\Console\Commands\WalkGraph;

use App\Http\Resources\WalkGraph\WalkGraphResource;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\Floor\Floor;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use App\Repositories\Interfaces\WalkGraph\WalkGraphRepositoryInterface;
use Database\Seeders\WalkGraphSeeder;
use ErrorException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class ImportWalkGraphsTest extends PublicTestCase
{
    use CreatesDungeon;

    private const string BASE_URL = 'https://walk-graph-export.test';

    private const int UI_MAP_ID = 9_991_001;

    private const int OTHER_UI_MAP_ID = 9_991_002;

    private const int THIRD_UI_MAP_ID = 9_991_003;

    private string $tempDir;

    private string $dungeonDataPath;

    private string $credentialsPath;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir         = sprintf('%s/walk-graph-import-test-%s', sys_get_temp_dir(), uniqid());
        $this->dungeonDataPath = sprintf('%s/dungeondata', $this->tempDir);
        $this->credentialsPath = sprintf('%s/credentials', $this->tempDir);
        File::ensureDirectoryExists($this->dungeonDataPath);
        File::put($this->credentialsPath, "exporter:secret\n");

        config([
            'keystoneguru.walk_graph.import_url'              => self::BASE_URL,
            'keystoneguru.walk_graph.import_credentials_path' => $this->credentialsPath,
            'keystoneguru.walk_graph.min_runs'                => 15,
        ]);
        Http::preventStrayRequests();
        WalkGraph::query()->whereIn('ui_map_id', [self::UI_MAP_ID, self::OTHER_UI_MAP_ID, self::THIRD_UI_MAP_ID])->delete();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            WalkGraph::query()->whereIn('ui_map_id', [self::UI_MAP_ID, self::OTHER_UI_MAP_ID, self::THIRD_UI_MAP_ID])->delete();
            File::deleteDirectory($this->tempDir);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenUiMap_writesSeederFile(): void
    {
        // Arrange
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        Http::fake([
            $this->showUrl(self::UI_MAP_ID) => Http::response($this->walkGraphBody(self::UI_MAP_ID, 'tww', 'the_blinding_vale')),
        ]);

        // Act
        $exitCode = $this->artisan('walkgraph:import', $this->commandOptions(['--ui-map' => [self::UI_MAP_ID]]))->run();

        // Assert
        $this->assertSame(0, $exitCode);
        $expectedContents = <<<'JSON'
            {
                "ui_map_id": 9991001,
                "cell_size": 3.0,
                "run_count": 40,
                "node_count": 3,
                "edge_count": 2,
                "largest_component_node_count": 3,
                "built_at": "2026-11-01T04:00:00+00:00",
                "edges": "%s"
            }

            JSON;
        $this->assertSame(
            sprintf($expectedContents, $this->encodedEdges(self::UI_MAP_ID)),
            File::get($this->walkGraphFilePath('tww', 'the_blinding_vale', self::UI_MAP_ID)),
        );
        Http::assertSent(static fn(Request $request): bool => $request->hasHeader('Authorization', sprintf('Basic %s', base64_encode('exporter:secret'))));
    }

    #[Test]
    public function handle_givenBearerTokenCredentials_sendsTheFileAsAuthorizationHeader(): void
    {
        // Arrange
        File::put($this->credentialsPath, "Bearer an-oauth-token\n");
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        Http::fake([
            $this->showUrl(self::UI_MAP_ID) => Http::response($this->walkGraphBody(self::UI_MAP_ID, 'tww', 'the_blinding_vale')),
        ]);

        // Act
        $exitCode = $this->artisan('walkgraph:import', $this->commandOptions(['--ui-map' => [self::UI_MAP_ID]]))->run();

        // Assert
        $this->assertSame(0, $exitCode);
        Http::assertSent(static fn(Request $request): bool => $request->hasHeader('Authorization', 'Bearer an-oauth-token'));
    }

    #[Test]
    public function handle_givenCredentialsPathInHomeDirectory_readsItFromTheHomeDirectory(): void
    {
        // Arrange
        $originalHome = getenv('HOME');
        putenv(sprintf('HOME=%s', $this->tempDir));
        config(['keystoneguru.walk_graph.import_credentials_path' => '~/credentials']);
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        Http::fake([
            $this->showUrl(self::UI_MAP_ID) => Http::response($this->walkGraphBody(self::UI_MAP_ID, 'tww', 'the_blinding_vale')),
        ]);

        try {
            // Act
            $exitCode = $this->artisan('walkgraph:import', $this->commandOptions(['--ui-map' => [self::UI_MAP_ID]]))->run();
        } finally {
            putenv($originalHome === false ? 'HOME' : sprintf('HOME=%s', $originalHome));
        }

        // Assert
        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->walkGraphFilePath('tww', 'the_blinding_vale', self::UI_MAP_ID));
    }

    #[Test]
    public function handle_givenDungeon_writesOneFilePerUiMap(): void
    {
        // Arrange - the vale uses two ui maps, the other dungeon's graph is in the same index
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        $this->makeDungeonDirectory('tww', 'other_dungeon');
        Http::fake([
            $this->indexUrl() => Http::response(['data' => [
                $this->summary(self::UI_MAP_ID, 'tww', 'the_blinding_vale'),
                $this->summary(self::OTHER_UI_MAP_ID, 'tww', 'the_blinding_vale'),
                $this->summary(self::THIRD_UI_MAP_ID, 'tww', 'other_dungeon'),
            ]]),
            $this->showUrl(self::UI_MAP_ID)       => Http::response($this->walkGraphBody(self::UI_MAP_ID, 'tww', 'the_blinding_vale')),
            $this->showUrl(self::OTHER_UI_MAP_ID) => Http::response($this->walkGraphBody(self::OTHER_UI_MAP_ID, 'tww', 'the_blinding_vale')),
            $this->showUrl(self::THIRD_UI_MAP_ID) => Http::response($this->walkGraphBody(self::THIRD_UI_MAP_ID, 'tww', 'other_dungeon')),
        ]);

        // Act
        $exitCode = $this->artisan('walkgraph:import', $this->commandOptions(['--dungeon' => 'the_blinding_vale']))->run();

        // Assert
        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->walkGraphFilePath('tww', 'the_blinding_vale', self::UI_MAP_ID));
        $this->assertFileExists($this->walkGraphFilePath('tww', 'the_blinding_vale', self::OTHER_UI_MAP_ID));
        $this->assertFileDoesNotExist($this->walkGraphFilePath('tww', 'other_dungeon', self::THIRD_UI_MAP_ID));
    }

    #[Test]
    public function handle_givenNoOptions_writesEveryGraphInTheIndex(): void
    {
        // Arrange
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        $this->makeDungeonDirectory('midnight', 'other_dungeon');
        Http::fake([
            $this->indexUrl() => Http::response(['data' => [
                $this->summary(self::UI_MAP_ID, 'tww', 'the_blinding_vale'),
                $this->summary(self::OTHER_UI_MAP_ID, 'midnight', 'other_dungeon'),
            ]]),
            $this->showUrl(self::UI_MAP_ID)       => Http::response($this->walkGraphBody(self::UI_MAP_ID, 'tww', 'the_blinding_vale')),
            $this->showUrl(self::OTHER_UI_MAP_ID) => Http::response($this->walkGraphBody(self::OTHER_UI_MAP_ID, 'midnight', 'other_dungeon')),
        ]);

        // Act
        $exitCode = $this->artisan('walkgraph:import', $this->commandOptions())->run();

        // Assert
        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->walkGraphFilePath('tww', 'the_blinding_vale', self::UI_MAP_ID));
        $this->assertFileExists($this->walkGraphFilePath('midnight', 'other_dungeon', self::OTHER_UI_MAP_ID));
    }

    #[Test]
    public function handle_givenExistingFile_overwritesIt(): void
    {
        // Arrange
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        $filePath = $this->walkGraphFilePath('tww', 'the_blinding_vale', self::UI_MAP_ID);
        File::put($filePath, '{"ui_map_id": 9991001, "run_count": 20}');
        Http::fake([
            $this->showUrl(self::UI_MAP_ID) => Http::response($this->walkGraphBody(self::UI_MAP_ID, 'tww', 'the_blinding_vale')),
        ]);

        // Act
        $exitCode = $this->artisan('walkgraph:import', $this->commandOptions(['--ui-map' => [self::UI_MAP_ID]]))->run();

        // Assert
        $this->assertSame(0, $exitCode);
        $this->assertSame(40, json_decode(File::get($filePath), true)['run_count']);
    }

    #[Test]
    public function handle_givenUnknownUiMap_failsWithoutWritingFiles(): void
    {
        // Arrange - the known ui map alone would be written
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        Http::fake([
            $this->showUrl(self::UI_MAP_ID)       => Http::response($this->walkGraphBody(self::UI_MAP_ID, 'tww', 'the_blinding_vale')),
            $this->showUrl(self::OTHER_UI_MAP_ID) => Http::response(['message' => 'Not found'], 404),
        ]);

        // Act
        $this->artisan('walkgraph:import', $this->commandOptions(['--ui-map' => [self::UI_MAP_ID, self::OTHER_UI_MAP_ID]]))
            ->expectsOutputToContain(sprintf('The walk graph of ui map %d does not exist', self::OTHER_UI_MAP_ID))
            ->assertExitCode(1)
            ->run();

        // Assert
        $this->assertFileDoesNotExist($this->walkGraphFilePath('tww', 'the_blinding_vale', self::UI_MAP_ID));
    }

    #[Test]
    #[DataProvider('refusedStatusProvider')]
    public function handle_givenRefusedCredentials_failsWithoutWritingFiles(int $status): void
    {
        // Arrange
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        Http::fake([
            $this->indexUrl() => Http::response(['message' => 'Unauthenticated.'], $status),
        ]);

        // Act
        $this->artisan('walkgraph:import', $this->commandOptions())
            ->expectsOutputToContain(sprintf('was refused by %s (HTTP %d)', $this->indexUrl(), $status))
            ->assertExitCode(1)
            ->run();

        // Assert
        $this->assertSame([], File::files(sprintf('%s/tww/the_blinding_vale', $this->dungeonDataPath)));
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function refusedStatusProvider(): array
    {
        return [
            'unauthenticated' => [401],
            'forbidden'       => [403],
        ];
    }

    #[Test]
    public function handle_givenMissingCredentialsFile_failsWithoutRequests(): void
    {
        // Arrange
        File::delete($this->credentialsPath);
        Http::fake();

        // Act
        $this->artisan('walkgraph:import', $this->commandOptions(['--ui-map' => [self::UI_MAP_ID]]))
            ->expectsOutputToContain(sprintf('Credentials file %s does not exist', $this->credentialsPath))
            ->assertExitCode(1)
            ->run();

        // Assert
        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('unusableDungeonDirectoryProvider')]
    public function handle_givenUnusableDungeonDirectory_failsWithoutWritingFiles(string $expansion, string $dungeonKey): void
    {
        // Arrange - the first ui map's directory exists, the second one's is unusable
        $this->makeDungeonDirectory('tww', 'the_blinding_vale');
        Http::fake([
            $this->showUrl(self::UI_MAP_ID)       => Http::response($this->walkGraphBody(self::UI_MAP_ID, 'tww', 'the_blinding_vale')),
            $this->showUrl(self::OTHER_UI_MAP_ID) => Http::response($this->walkGraphBody(self::OTHER_UI_MAP_ID, $expansion, $dungeonKey)),
        ]);

        // Act - writing into a missing directory throws; caught so the files written before it are asserted on
        $pendingCommand = $this->artisan('walkgraph:import', $this->commandOptions(['--ui-map' => [self::UI_MAP_ID, self::OTHER_UI_MAP_ID]]))
            ->expectsOutputToContain('which does not exist')
            ->assertExitCode(1);

        try {
            $pendingCommand->run();
        } catch (ErrorException) {
        }

        // Assert
        $this->assertSame([], File::allFiles($this->dungeonDataPath));
        $this->assertFileDoesNotExist(sprintf('%s/walk_graph_%d.json', $this->dungeonDataPath, self::OTHER_UI_MAP_ID));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function unusableDungeonDirectoryProvider(): array
    {
        return [
            'missing directory'                     => ['tww', 'a_dungeon_without_data'],
            'dungeon key leaving the expansion dir' => ['tww', '..'],
        ];
    }

    #[Test]
    public function handle_givenExportedGraph_writesFileThatLoadsThroughWalkGraphSeeder(): void
    {
        // Arrange - the export's own resource renders the response, the graph is then gone from the database
        $dungeon = $this->createDungeon();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();
        $floor->update(['ui_map_id' => self::UI_MAP_ID]);
        $edgeCounts = [WalkGrid::edgeKey(0, 0, 1, 0) => 30, WalkGrid::edgeKey(1, 0, 2, 0) => 12];
        WalkGraph::factory()->create([
            'ui_map_id'                    => self::UI_MAP_ID,
            'cell_size'                    => 3.0,
            'run_count'                    => 40,
            'node_count'                   => 3,
            'edge_count'                   => 2,
            'largest_component_node_count' => 3,
            'edges'                        => WalkGrid::encodeEdgeCounts($edgeCounts),
            'built_at'                     => '2026-11-01 04:00:00',
        ]);
        $exportedGraph = app(WalkGraphRepositoryInterface::class)->findByUiMapIdWithFloors(self::UI_MAP_ID);
        $responseBody  = new WalkGraphResource($exportedGraph)->response(request())->getData(true);
        WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
        $this->makeDungeonDirectory($dungeon->expansion->shortname, $dungeon->key);
        Http::fake([$this->showUrl(self::UI_MAP_ID) => Http::response($responseBody)]);

        // Act
        $exitCode = $this->artisan('walkgraph:import', $this->commandOptions(['--ui-map' => [self::UI_MAP_ID]]))->run();
        new WalkGraphSeeder($this->dungeonDataPath)->run();

        // Assert
        $this->assertSame(0, $exitCode);
        $seededGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->first();
        $this->assertNotNull($seededGraph);
        $this->assertSame(WalkGraphSource::Seeder, $seededGraph->source);
        $this->assertSame(3.0, $seededGraph->cell_size);
        $this->assertSame(40, $seededGraph->run_count);
        $this->assertSame(3, $seededGraph->largest_component_node_count);
        $this->assertSame('2026-11-01 04:00:00', $seededGraph->built_at->toDateTimeString());
        $this->assertSame($edgeCounts, WalkGrid::decodeEdgeCounts($seededGraph->edges));
    }

    /**
     * @param  array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function commandOptions(array $options = []): array
    {
        return array_merge(['--dungeondata-path' => $this->dungeonDataPath], $options);
    }

    private function makeDungeonDirectory(string $expansion, string $dungeonKey): void
    {
        File::ensureDirectoryExists(sprintf('%s/%s/%s', $this->dungeonDataPath, $expansion, $dungeonKey));
    }

    private function walkGraphFilePath(string $expansion, string $dungeonKey, int $uiMapId): string
    {
        return sprintf('%s/%s/%s/walk_graph_%d.json', $this->dungeonDataPath, $expansion, $dungeonKey, $uiMapId);
    }

    private function indexUrl(): string
    {
        return sprintf('%s/api/v1/walkgraphs', self::BASE_URL);
    }

    private function showUrl(int $uiMapId): string
    {
        return sprintf('%s/api/v1/walkgraphs/%d', self::BASE_URL, $uiMapId);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(int $uiMapId, string $expansion, string $dungeonKey): array
    {
        return [
            'ui_map_id'                    => $uiMapId,
            'run_count'                    => 40,
            'node_count'                   => 3,
            'edge_count'                   => 2,
            'largest_component_node_count' => 3,
            'built_at'                     => '2026-11-01T04:00:00+00:00',
            'dungeons'                     => [[
                'key'       => $dungeonKey,
                'name'      => 'A Dungeon',
                'expansion' => $expansion,
                'floors'    => [['id' => 1, 'index' => 1, 'name' => 'A Floor']],
            ]],
        ];
    }

    /**
     * @return array{data: array<string, mixed>}
     */
    private function walkGraphBody(int $uiMapId, string $expansion, string $dungeonKey): array
    {
        // Keys deliberately in another order than the file's, and cell_size as JSON renders 3.0
        return ['data' => array_merge(
            ['edges' => $this->encodedEdges($uiMapId), 'cell_size' => 3],
            $this->summary($uiMapId, $expansion, $dungeonKey),
        )];
    }

    private function encodedEdges(int $uiMapId): string
    {
        return base64_encode(WalkGrid::encodeEdgeCounts([
            WalkGrid::edgeKey(0, 0, 1, 0) => 30,
            WalkGrid::edgeKey(1, 0, 2, 0) => $uiMapId - self::UI_MAP_ID + 12,
        ]));
    }
}
