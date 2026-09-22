<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Right-clicking an enemy on a route/explore map opens its NPC Compendium page in a new tab. The map
 * JS reads the page's location off the `npcCompendiumBaseUrl` inline option, which only the real
 * Blade render can prove is wired up correctly.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteViewNpcCompendiumOptionsTest extends PublicTestCase
{
    #[Test]
    public function view_givenPublishedRoute_exposesNpcCompendiumBaseUrlToMapJs(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $response->assertDontSee('"npcCompendiumEnabled"', false);
            $response->assertSee(
                '"npcCompendiumBaseUrl":"' . str_replace('/', '\/', url('/compendium/npc')),
                false,
            );
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * A published, non-sandbox route owned by $owner. Sandbox routes (which the factory creates by
     * default) are editable by anyone, so expires_at must be null here. The view page is public, so
     * plain factory users suffice - no role is needed to reach it.
     */
    private function createRoute(User $owner): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    /**
     * dungeonroute.view always redirects to the default floor, so tests follow redirects.
     */
    private function viewUrl(DungeonRoute $route): string
    {
        return route('dungeonroute.view', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
