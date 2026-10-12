<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Each test adds dungeon_routes.dungeon_start_map_icon_id to the migrated test schema for its own duration and
 * drops it again afterwards.
 */
#[Group('Migrations')]
#[Group('DungeonStart')]
final class DropDungeonStartMapIconIdFromDungeonRoutesTableTest extends PublicTestCase
{
    private const string MIGRATION = 'migrations/2026_10_12_000000_drop_dungeon_start_map_icon_id_from_dungeon_routes_table.php';

    private const int JSON_DUNGEON_START_ID = 987654;

    private const int LEGACY_DUNGEON_START_MAP_ICON_TYPE_ID = 10;

    private string $dungeonDataDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dungeonDataDir = sprintf('%s/drop-dungeon-start-map-icon-id-%s', sys_get_temp_dir(), uniqid());
        if (!Schema::hasColumn('dungeon_routes', 'dungeon_start_map_icon_id')) {
            Schema::table('dungeon_routes', function (Blueprint $table) {
                $table->unsignedBigInteger('dungeon_start_map_icon_id')->nullable();
                $table->index('dungeon_start_map_icon_id');
            });
        }
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dungeonDataDir);
        if (Schema::hasColumn('dungeon_routes', 'dungeon_start_map_icon_id')) {
            Schema::table('dungeon_routes', function (Blueprint $table) {
                $table->dropColumn('dungeon_start_map_icon_id');
            });
        }

        parent::tearDown();
    }

    #[Test]
    public function up_givenRouteWithStartMapIconMatchingTheJson_setsDungeonStartIdAndDropsTheColumn(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $mapIcon = $this->createLegacyDungeonStartMapIcon($route, -100.25, 150.5);
        $this->pointRouteAtMapIcon($route, $mapIcon);
        $this->writeDungeonStartsJson($route, -100.25, 150.5);

        try {
            // Act
            $result = $this->backfill();

            // Assert
            $this->assertSame(self::JSON_DUNGEON_START_ID, $route->fresh()->dungeon_start_id);
            $this->assertGreaterThanOrEqual(1, $result['updated']);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    #[Test]
    public function up_givenAMigratedRoutesTable_dropsTheColumn(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);

        // Act
        $migration->up();

        // Assert
        $this->assertFalse(Schema::hasColumn('dungeon_routes', 'dungeon_start_map_icon_id'));
    }

    #[Test]
    public function up_givenRouteWithoutAMatchingDungeonStart_logsItsSelectionBeforeDropping(): void
    {
        // Arrange
        $route     = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $mapIconId = (int)MapIcon::query()->max('id') + 1000;
        DB::table('dungeon_routes')->where('id', $route->id)->update(['dungeon_start_map_icon_id' => $mapIconId]);
        $log       = Log::spy();
        $migration = require database_path(self::MIGRATION);

        try {
            // Act
            $migration->up();

            // Assert
            $log->shouldHaveReceived('warning')->withArgs(
                static fn(string $message, array $context = []): bool => ($context['dungeon_route_id'] ?? null) === $route->id
                    && ($context['dungeon_start_map_icon_id'] ?? null) === $mapIconId
                    && ($context['public_key'] ?? null) === $route->public_key,
            )->once();
            $this->assertFalse(Schema::hasColumn('dungeon_routes', 'dungeon_start_map_icon_id'));
        } finally {
            $route->delete();
        }
    }

    #[Test]
    public function down_givenDroppedColumn_restoresItAsNullable(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropColumn('dungeon_start_map_icon_id');
        });

        // Act
        $migration->down();

        // Assert
        $this->assertTrue(Schema::hasColumn('dungeon_routes', 'dungeon_start_map_icon_id'));
    }

    #[Test]
    public function backfillDungeonStartIds_givenStartMapIconAtAnotherPosition_leavesDungeonStartIdNull(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $mapIcon = $this->createLegacyDungeonStartMapIcon($route, -100.25, 150.5);
        $this->pointRouteAtMapIcon($route, $mapIcon);
        $this->writeDungeonStartsJson($route, -50.0, 150.5);

        try {
            // Act
            $this->backfill();

            // Assert
            $this->assertNull($route->fresh()->dungeon_start_id);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    #[Test]
    public function backfillDungeonStartIds_givenStartMapIconWithOnlyItsLngDifferent_leavesDungeonStartIdNull(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $mapIcon = $this->createLegacyDungeonStartMapIcon($route, -100.25, 150.5);
        $this->pointRouteAtMapIcon($route, $mapIcon);
        $this->writeDungeonStartsJson($route, -100.25, 99.0);

        try {
            // Act
            $this->backfill();

            // Assert
            $this->assertNull($route->fresh()->dungeon_start_id);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    #[Test]
    public function backfillDungeonStartIds_givenMapIconThatIsNotADungeonStart_leavesDungeonStartIdNull(): void
    {
        // Arrange - a comment icon sitting exactly where the dungeon start in the json is
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $mapIcon = $this->createLegacyDungeonStartMapIcon($route, -100.25, 150.5, MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT]);
        $this->pointRouteAtMapIcon($route, $mapIcon);
        $this->writeDungeonStartsJson($route, -100.25, 150.5);

        try {
            // Act
            $this->backfill();

            // Assert
            $this->assertNull($route->fresh()->dungeon_start_id);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    #[Test]
    public function backfillDungeonStartIds_givenDungeonStartOfAnotherMappingVersionAtTheSamePosition_leavesDungeonStartIdNull(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        $mapIcon = $this->createLegacyDungeonStartMapIcon($route, -100.25, 150.5);
        $this->pointRouteAtMapIcon($route, $mapIcon);
        $this->writeDungeonStartsJson($route, -100.25, 150.5, $route->mapping_version_id + 999999);

        try {
            // Act
            $this->backfill();

            // Assert
            $this->assertNull($route->fresh()->dungeon_start_id);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    #[Test]
    public function backfillDungeonStartIds_givenStartMapIconThatNoLongerExists_leavesDungeonStartIdNull(): void
    {
        // Arrange - the seeder re-creates map icons with new ids, so an old choice can point at nothing
        $route = DungeonRoute::factory()->create(['dungeon_start_id' => null]);
        DB::table('dungeon_routes')->where('id', $route->id)->update(['dungeon_start_map_icon_id' => (int)MapIcon::query()->max('id') + 1000]);
        $this->writeDungeonStartsJson($route, -100.25, 150.5);

        try {
            // Act
            $this->backfill();

            // Assert
            $this->assertNull($route->fresh()->dungeon_start_id);
        } finally {
            $route->delete();
        }
    }

    #[Test]
    public function backfillDungeonStartIds_givenRouteThatAlreadyHasADungeonStartId_leavesItUnchanged(): void
    {
        // Arrange
        $route   = DungeonRoute::factory()->create(['dungeon_start_id' => 42]);
        $mapIcon = $this->createLegacyDungeonStartMapIcon($route, -100.25, 150.5);
        $this->pointRouteAtMapIcon($route, $mapIcon);
        $this->writeDungeonStartsJson($route, -100.25, 150.5);

        try {
            // Act
            $this->backfill();

            // Assert
            $this->assertSame(42, $route->fresh()->dungeon_start_id);
        } finally {
            $mapIcon->delete();
            $route->delete();
        }
    }

    /**
     * @return array{updated: int, unmatched: int}
     */
    private function backfill(): array
    {
        $migration = require database_path(self::MIGRATION);

        return $migration->backfillDungeonStartIds($this->dungeonDataDir);
    }

    private function pointRouteAtMapIcon(DungeonRoute $route, MapIcon $mapIcon): void
    {
        DB::table('dungeon_routes')->where('id', $route->id)->update(['dungeon_start_map_icon_id' => $mapIcon->id]);
    }

    private function createLegacyDungeonStartMapIcon(
        DungeonRoute $route,
        float        $lat,
        float        $lng,
        int          $mapIconTypeId = self::LEGACY_DUNGEON_START_MAP_ICON_TYPE_ID,
    ): MapIcon {
        return MapIcon::create([
            'mapping_version_id' => $route->mapping_version_id,
            'floor_id'           => $route->dungeon->floors->first()->id,
            'dungeon_route_id'   => null,
            'team_id'            => null,
            'map_icon_type_id'   => $mapIconTypeId,
            'lat'                => $lat,
            'lng'                => $lng,
            'comment'            => null,
            'permanent_tooltip'  => false,
            'seasonal_index'     => 0,
        ]);
    }

    private function writeDungeonStartsJson(DungeonRoute $route, float $lat, float $lng, ?int $mappingVersionId = null): void
    {
        $floorDir = sprintf('%s/expansion/dungeon/1', $this->dungeonDataDir);
        File::ensureDirectoryExists($floorDir);
        File::put(sprintf('%s/dungeon_starts.json', $floorDir), json_encode([[
            'id'                 => self::JSON_DUNGEON_START_ID,
            'mapping_version_id' => $mappingVersionId ?? $route->mapping_version_id,
            'floor_id'           => $route->dungeon->floors->first()->id,
            'target_dungeon_id'  => null,
            'lat'                => $lat,
            'lng'                => $lng,
            'comment'            => null,
        ]], JSON_PRETTY_PRINT));
    }
}
