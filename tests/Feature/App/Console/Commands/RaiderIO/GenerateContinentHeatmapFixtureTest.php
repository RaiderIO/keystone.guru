<?php

namespace Tests\Feature\App\Console\Commands\RaiderIO;

use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('GenerateContinentHeatmapFixture')]
final class GenerateContinentHeatmapFixtureTest extends PublicTestCase
{
    private string $outputDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->outputDir = storage_path(sprintf('framework/testing/continent-heatmap-fixture-%s', uniqid()));
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            File::deleteDirectory($this->outputDir);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenContinentMapId_writesAFixtureTheMockReads(): void
    {
        // Arrange
        $dungeon = Dungeon::with('floors')->where('key', DungeonKey::EASTERN_KINGDOMS->value)->firstOrFail();

        // Act
        $exitCode = $this->artisan('raiderio:generatecontinentheatmapfixture', [
            'mapId'            => $dungeon->map_id,
            '--cells-per-zone' => 10,
            '--output-dir'     => $this->outputDir,
        ])->run();

        // Assert
        $this->assertSame(0, $exitCode);
        $fixturePath = sprintf('%s/heatmap_grid_map_%d.json', $this->outputDir, $dungeon->map_id);
        $this->assertFileExists($fixturePath);
        $fixture = json_decode((string)file_get_contents($fixturePath), true);
        $this->assertSame(
            $dungeon->floors->where('facade', false)->pluck('id')->sort()->values()->all(),
            collect(array_keys($fixture[CombatLogEventEventType::PlayerDeath->value]['gridsByFloor']))->sort()->values()->all(),
        );
    }

    #[Test]
    public function handle_givenMapIdOfNoContinent_failsWithoutWritingAFixture(): void
    {
        // Arrange
        $mapId = 999999;

        // Act
        $exitCode = $this->artisan('raiderio:generatecontinentheatmapfixture', [
            'mapId'        => $mapId,
            '--output-dir' => $this->outputDir,
        ])->run();

        // Assert
        $this->assertSame(1, $exitCode);
        $this->assertFileDoesNotExist(sprintf('%s/heatmap_grid_map_%d.json', $this->outputDir, $mapId));
    }
}
