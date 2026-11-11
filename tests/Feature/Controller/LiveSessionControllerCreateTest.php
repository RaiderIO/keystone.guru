<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('LiveSession')]
final class LiveSessionControllerCreateTest extends PublicTestCase
{
    #[Test]
    public function create_givenPostRequest_createsALiveSessionAndRedirectsToIt(): void
    {
        // Arrange
        $owner = null;
        $route = null;

        try {
            [$owner, $route] = $this->createOwnerWithRoute();

            // Act
            $response = $this->actingAs($owner)->post($this->createUrl($route));

            // Assert
            /** @var LiveSession $liveSession */
            $liveSession = LiveSession::query()->where('dungeon_route_id', $route->id)->firstOrFail();
            $this->assertSame($owner->id, $liveSession->user_id);
            $response->assertRedirect(route('dungeonroute.livesession.view', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'liveSession'  => $liveSession,
            ]));
        } finally {
            $this->cleanup($owner, $route);
        }
    }

    #[Test]
    public function create_givenGetRequest_createsNoLiveSession(): void
    {
        // Arrange
        $owner = null;
        $route = null;

        try {
            [$owner, $route] = $this->createOwnerWithRoute();

            // Act
            $this->actingAs($owner)->get($this->createUrl($route));

            // Assert - a GET falls through to the read-only view-floor route, which takes "live" for a floor index
            $this->assertSame('dungeonroute.view.floor', Route::currentRouteName());
            $this->assertSame(0, LiveSession::query()->where('dungeon_route_id', $route->id)->count());
        } finally {
            $this->cleanup($owner, $route);
        }
    }

    /**
     * @return array{0: User, 1: DungeonRoute}
     */
    private function createOwnerWithRoute(): array
    {
        $owner = User::factory()->create();
        $owner->addRole(Role::firstWhere('name', Role::ROLE_USER));

        $route = DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);

        return [$owner, $route];
    }

    private function createUrl(DungeonRoute $route): string
    {
        return route('dungeonroute.livesession.create', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }

    private function cleanup(?User $owner, ?DungeonRoute $route): void
    {
        if ($route !== null) {
            // Mass delete on purpose: LiveSession's "deleting" hook cascades into overpulled_enemies,
            // a table no migration creates, so $liveSession->delete() throws on a migrated database.
            LiveSession::query()->where('dungeon_route_id', $route->id)->delete();
            $route->delete();
        }

        $owner?->delete();
    }
}
