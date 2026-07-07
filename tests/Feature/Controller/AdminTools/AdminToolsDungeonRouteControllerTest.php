<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Jobs\UpgradeDungeonRouteMappingVersion;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Tests use dungeon_id=1, game_version_id=7 which has:
 *   - MappingVersion id=612 (version 2) — not the latest
 *   - MappingVersion id=628 (version 3) — the latest
 */
#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsDungeonRouteControllerTest extends PublicTestCase
{
    private const int OLD_MAPPING_VERSION_ID    = 612;
    private const int LATEST_MAPPING_VERSION_ID = 628;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function dungeonrouteMappingVersions_givenAuthenticatedAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.dungeonroute.mappingversionusage'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function dungeonrouteMappingVersionsUpgrade_givenNonLatestMappingVersion_dispatchesJobPerRoute(): void
    {
        // Arrange
        Queue::fake();

        $oldMappingVersion = MappingVersion::findOrFail(self::OLD_MAPPING_VERSION_ID);

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create(['mapping_version_id' => $oldMappingVersion->id]);

        try {
            // Act
            $response = $this->post(route('admin.tools.dungeonroute.mappingversionusage.upgrade', [
                'mappingVersion' => $oldMappingVersion->id,
            ]));

            // Assert
            $response->assertRedirect(route('admin.tools.dungeonroute.mappingversionusage'));
            Queue::assertPushed(UpgradeDungeonRouteMappingVersion::class, 1);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function dungeonrouteMappingVersionsUpgrade_givenLatestMappingVersion_dispatchesNoJobs(): void
    {
        // Arrange
        Queue::fake();

        // Act
        $response = $this->post(route('admin.tools.dungeonroute.mappingversionusage.upgrade', [
            'mappingVersion' => self::LATEST_MAPPING_VERSION_ID,
        ]));

        // Assert
        $response->assertRedirect(route('admin.tools.dungeonroute.mappingversionusage'));
        Queue::assertNothingPushed();
    }
}
