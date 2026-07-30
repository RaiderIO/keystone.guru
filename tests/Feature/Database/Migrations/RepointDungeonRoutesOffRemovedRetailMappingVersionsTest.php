<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the data repair that lets Cathedral of Eternal Night and Maw of Souls lose their retail mapping
 * version without taking their existing routes down with it - see the migration's own docblock for why
 * those mapping versions go away, and #3739.
 *
 * The migration is idempotent (it selects routes that are not on a surviving mapping version, so a second
 * run matches nothing), which is what makes it safe to invoke directly against the shared seeded database.
 */
#[Group('MappingVersion')]
final class RepointDungeonRoutesOffRemovedRetailMappingVersionsTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_07_30_000000_repoint_dungeon_routes_off_removed_retail_mapping_versions.php';

    #[Test]
    public function up_givenRouteOnARemovedMappingVersion_repointsItToTheCurrentLegionRemixMappingVersion(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', 'cathedralofeternalnight')->firstOrFail();
        /** @var GameVersion $legionRemix */
        $legionRemix = GameVersion::query()->where('key', GameVersion::GAME_VERSION_LEGION_REMIX)->firstOrFail();

        $expectedMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($legionRemix);
        $this->assertNotNull($expectedMappingVersion, 'Cathedral of Eternal Night must keep a Legion Remix mapping version.');

        // An id that no mapping version has, standing in for the removed retail one
        $removedMappingVersionId = MappingVersion::query()->max('id') + 1000;

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $removedMappingVersionId,
        ]);

        try {
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
            $dungeonRoute->forceDelete();
        }
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
