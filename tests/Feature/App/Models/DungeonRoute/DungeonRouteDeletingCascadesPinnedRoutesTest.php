<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use App\Models\UserPinnedDungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards that deleting a route cleans up any {@see UserPinnedDungeonRoute} pointing at it, the same
 * way {@see DungeonRoute::boot()}'s `deleting` listener already cleans up ratings/favorites/etc -
 * this project uses no foreign keys, so nothing at the database level would otherwise stop the pin
 * from dangling.
 */
#[Group('DungeonRoute')]
final class DungeonRouteDeletingCascadesPinnedRoutesTest extends PublicTestCase
{
    #[Test]
    public function delete_givenRouteIsPinnedByAUser_deletesThePin(): void
    {
        // Arrange
        $creator      = User::factory()->create();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $creator->id]);

        $pin                   = new UserPinnedDungeonRoute();
        $pin->user_id          = $creator->id;
        $pin->dungeon_route_id = $dungeonRoute->id;
        $pin->order            = 0;
        $pin->save();

        try {
            // Act
            $dungeonRoute->delete();

            // Assert
            $this->assertSame(
                0,
                UserPinnedDungeonRoute::where('dungeon_route_id', $dungeonRoute->id)->count(),
                'Deleting a route must delete any pin that referenced it, not leave it dangling',
            );
        } finally {
            UserPinnedDungeonRoute::where('user_id', $creator->id)->delete();
            $creator->delete();
        }
    }
}
