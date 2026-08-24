<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRouteChange;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use Exception;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('MapIcon')]
final class AjaxMapIconControllerTest extends DungeonRouteTestBase
{
    #[Test]
    public function delete_givenAnExistingMapIcon_deletesIt(): void
    {
        // Arrange
        $mapIcon = $this->createMapIcon();

        // Act
        $response = $this->delete($this->deleteUrl($mapIcon));

        // Assert
        $response->assertNoContent();
        $this->assertEquals(0, $this->dungeonRoute->mapicons()->count());
    }

    /**
     * Guards #4264: delete() cascaded the icon's awakened obelisk links away (MapIcon::deleting) and
     * only then wrote the change log row and touched the route, with no transaction around any of
     * it. A failure at the change log left the icon permanently gone while the route's change log -
     * the team's audit trail - had no record that it ever happened.
     */
    #[Test]
    public function delete_givenTheChangeLogWriteFails_rollsBackTheMapIconDelete(): void
    {
        // Arrange
        $mapIcon = $this->createMapIcon();

        // Fail the change log write, which is the write that follows the cascading delete
        DungeonRouteChange::creating(static function (): never {
            throw new Exception('Simulated failure writing the change log');
        });

        try {
            // Act
            $response = $this->delete($this->deleteUrl($mapIcon));

            // Assert - the client is told it failed, and the icon is still there to delete again
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertEquals(1, $this->dungeonRoute->mapicons()->count());
        } finally {
            // Remove only the listener registered above - DungeonRouteChange::flushEventListeners()
            // would also wipe its own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);

            $mapIcon->delete();
        }
    }

    private function createMapIcon(): MapIcon
    {
        /** @var Floor $randomFloor */
        $randomFloor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        return MapIcon::factory()->create([
            'dungeon_route_id'   => $this->dungeonRoute->id,
            'mapping_version_id' => null,
            'floor_id'           => $randomFloor->id,
        ]);
    }

    private function deleteUrl(MapIcon $mapIcon): string
    {
        return sprintf('/ajax/%s/mapicon/%s', $this->dungeonRoute->getRouteKey(), $mapIcon->getRouteKey());
    }
}
