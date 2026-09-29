<?php

namespace Tests\Feature\App\Console\Commands\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
#[Group('DungeonStart')]
final class BackfillDungeonStartIdTest extends PublicTestCase
{
    private const int JSON_DUNGEON_START_ID = 987654;

    private string $dungeonDataDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dungeonDataDir = sprintf('%s/backfill-dungeon-start-id-%s', sys_get_temp_dir(), uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dungeonDataDir);

        parent::tearDown();
    }

    #[Test]
    public function handle_givenRouteWithStartMapIconMatchingTheJson_setsDungeonStartId(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $mapIcon = $this->createDungeonStartMapIcon($route, -100.25, 150.5);
        $route->update(['dungeon_start_map_icon_id' => $mapIcon->id]);
        $this->writeDungeonStartsJson($route, -100.25, 150.5);

        try {
            // Act
            $this->artisan('dungeonroute:backfilldungeonstartid', ['--dir' => $this->dungeonDataDir])->assertSuccessful();

            // Assert
            $this->assertSame(self::JSON_DUNGEON_START_ID, $route->fresh()->dungeon_start_id);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    #[Test]
    public function handle_givenStartMapIconAtAnotherPosition_leavesDungeonStartIdNull(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $mapIcon = $this->createDungeonStartMapIcon($route, -100.25, 150.5);
        $route->update(['dungeon_start_map_icon_id' => $mapIcon->id]);
        $this->writeDungeonStartsJson($route, -50.0, 150.5);

        try {
            // Act
            $this->artisan('dungeonroute:backfilldungeonstartid', ['--dir' => $this->dungeonDataDir])->assertSuccessful();

            // Assert
            $this->assertNull($route->fresh()->dungeon_start_id);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    #[Test]
    public function handle_givenStartMapIconThatNoLongerExists_leavesDungeonStartIdNull(): void
    {
        // Arrange - the seeder re-creates map icons with new ids, so an old choice can point at nothing
        $route = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $route->update(['dungeon_start_map_icon_id' => (int)MapIcon::query()->max('id') + 1000]);
        $this->writeDungeonStartsJson($route, -100.25, 150.5);

        try {
            // Act
            $this->artisan('dungeonroute:backfilldungeonstartid', ['--dir' => $this->dungeonDataDir])->assertSuccessful();

            // Assert
            $this->assertNull($route->fresh()->dungeon_start_id);
        } finally {
            $route->delete();
        }
    }

    #[Test]
    public function handle_givenRouteThatAlreadyHasADungeonStartId_leavesItUnchanged(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => 42]);
        $mapIcon = $this->createDungeonStartMapIcon($route, -100.25, 150.5);
        $route->update(['dungeon_start_map_icon_id' => $mapIcon->id]);
        $this->writeDungeonStartsJson($route, -100.25, 150.5);

        try {
            // Act
            $this->artisan('dungeonroute:backfilldungeonstartid', ['--dir' => $this->dungeonDataDir])->assertSuccessful();

            // Assert
            $this->assertSame(42, $route->fresh()->dungeon_start_id);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    private function createDungeonStartMapIcon(DungeonRoute $route, float $lat, float $lng): MapIcon
    {
        return MapIcon::create([
            'mapping_version_id' => $route->mapping_version_id,
            'floor_id'           => $route->dungeon->floors->first()->id,
            'dungeon_route_id'   => null,
            'team_id'            => null,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START],
            'lat'                => $lat,
            'lng'                => $lng,
            'comment'            => null,
            'permanent_tooltip'  => false,
            'seasonal_index'     => 0,
        ]);
    }

    private function writeDungeonStartsJson(DungeonRoute $route, float $lat, float $lng): void
    {
        $floorDir = sprintf('%s/expansion/dungeon/1', $this->dungeonDataDir);
        File::ensureDirectoryExists($floorDir);
        File::put(sprintf('%s/dungeon_starts.json', $floorDir), json_encode([[
            'id'                 => self::JSON_DUNGEON_START_ID,
            'mapping_version_id' => $route->mapping_version_id,
            'floor_id'           => $route->dungeon->floors->first()->id,
            'target_dungeon_id'  => null,
            'lat'                => $lat,
            'lng'                => $lng,
            'comment'            => null,
        ]], JSON_PRETTY_PRINT));
    }
}
