<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the data repair that lets Cathedral of Eternal Night and Maw of Souls lose their retail mapping
 * version without taking their existing routes down with it - see the migration's own docblock for why
 * those mapping versions go away, and #3739.
 *
 * Running the migration touches far more than the fixture - it repoints every route on those two dungeons
 * that is not already on a surviving mapping version, and its down() is deliberately a no-op because the
 * mapping versions it moves routes off no longer exist. Tests here run against a persistent seeded database
 * that is never refreshed, so the call is wrapped in a transaction that is always rolled back; that also
 * disposes of the fixture route.
 */
#[Group('MappingVersion')]
final class RepointDungeonRoutesOffRemovedRetailMappingVersionsTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_07_30_000000_repoint_dungeon_routes_off_removed_retail_mapping_versions.php';

    #[Test]
    #[DataProvider('affectedDungeonKeysProvider')]
    public function up_givenRouteOnARemovedMappingVersion_repointsItToTheCurrentLegionRemixMappingVersion(
        string $dungeonKey,
    ): void {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', $dungeonKey)->firstOrFail();
        /** @var GameVersion $legionRemix */
        $legionRemix = GameVersion::query()->where('key', GameVersion::GAME_VERSION_LEGION_REMIX)->firstOrFail();

        $expectedMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($legionRemix);
        $this->assertNotNull($expectedMappingVersion, sprintf('%s must keep a Legion Remix mapping version.', $dungeonKey));

        // An id that no mapping version has, standing in for the removed retail one
        $removedMappingVersionId = MappingVersion::query()->max('id') + 1000;

        // The migration repoints every route on this dungeon that is not on a surviving mapping version, not
        // just the fixture, and its down() is a no-op by design - so it only ever runs inside a transaction
        // here. The rollback disposes of the fixture route too.
        DB::beginTransaction();

        try {
            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $removedMappingVersionId,
            ]);

            // Act
            $migration = require database_path(self::MIGRATION);
            $migration->up();

            // Assert
            $this->assertSame(
                $expectedMappingVersion->id,
                $dungeonRoute->fresh()->mapping_version_id,
                'The route should have been repointed onto the current Legion Remix mapping version.',
            );
        } finally {
            DB::rollBack();
        }
    }

    /** @return array<string, array{string}> */
    public static function affectedDungeonKeysProvider(): array
    {
        return [
            'cathedral of eternal night' => ['cathedralofeternalnight'],
            'maw of souls'               => ['mawofsouls'],
        ];
    }

    /**
     * The whole point of the repair: after it, nothing is left pointing at a mapping version that the
     * seeder no longer creates. Meaningful against a production-like database; on a freshly seeded one it
     * simply confirms the seeded routes are consistent.
     */
    #[Test]
    public function dungeonRoutes_givenSeededDatabase_allReferenceAnExistingMappingVersion(): void
    {
        // Arrange
        // Act
        $dangling = DungeonRoute::query()
            ->whereNotNull('mapping_version_id')
            ->whereDoesntHave('mappingVersion')
            ->count();

        // Assert
        $this->assertSame(0, $dangling, sprintf('%d dungeon routes point at a mapping version that does not exist', $dangling));
    }
}
