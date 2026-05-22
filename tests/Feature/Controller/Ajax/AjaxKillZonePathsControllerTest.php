<?php

namespace Tests\Feature\Controller\Ajax;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('KillZone')]
final class AjaxKillZonePathsControllerTest extends DungeonRouteTestBase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);

        $this->dungeonRoute->delete();
        $this->dungeonRoute = $this->createNonFacadeDungeonRoute();
    }

    #[Test]
    public function paths_givenRouteWithNoKillZones_returnsEmptyPaths(): void
    {
        // Act
        $response = $this->get(sprintf('/ajax/%s/killzone/paths', $this->dungeonRoute->public_key));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['killzone_paths']);
        $this->assertEmpty($response->json('killzone_paths'));
    }
}
