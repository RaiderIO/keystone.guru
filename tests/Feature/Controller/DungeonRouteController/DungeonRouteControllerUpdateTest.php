<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Route settings are saved through `PATCH /ajax/{dungeonroute}` (`api.dungeonroute.update`); the
 * web edit URL `/route/{dungeon}/{dungeonroute}/{title?}/edit` accepts GET only.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
#[Group('DungeonRouteUpdate')]
final class DungeonRouteControllerUpdateTest extends DungeonRouteControllerCreateTestBase
{
    #[Test]
    public function patchEdit_givenOwnedRoute_returnsMethodNotAllowedAndLeavesTheRouteUntouched(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        $dungeon = $this->getActiveDungeon();
        $route   = DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
            'title'              => 'Original title',
        ]);
        $sinceId = (int)DungeonRoute::query()->max('id');

        try {
            // Act
            $response = $this->actingAs($user)->patch(route('dungeonroute.edit', [
                'dungeon'      => $dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]), [
                'dungeon_id'          => $dungeon->id,
                'dungeon_route_title' => 'Updated title',
            ]);

            // Assert
            $response->assertMethodNotAllowed();
            $this->assertNull($this->latestRouteSince($sinceId), 'A PATCH on the edit URL must not create a route');
            $this->assertSame('Original title', $route->refresh()->title);
        } finally {
            $this->latestRouteSince($sinceId)?->delete();
            $route->delete();
            $user->delete();
        }
    }

    #[Test]
    public function routes_givenBootedApplication_registerOnlyTheAjaxUpdateRoute(): void
    {
        // Arrange

        // Act
        $hasWebUpdateRoute  = Route::has('dungeonroute.update');
        $hasAjaxUpdateRoute = Route::has('api.dungeonroute.update');

        // Assert
        self::assertFalse($hasWebUpdateRoute);
        self::assertTrue($hasAjaxUpdateRoute);
    }
}
