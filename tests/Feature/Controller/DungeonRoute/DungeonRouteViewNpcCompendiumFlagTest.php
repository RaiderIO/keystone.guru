<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Features\NpcCompendium;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3957: right-clicking an enemy on a route/explore map should open its NPC Compendium page in a
 * new tab when the NpcCompendium feature flag is enabled, instead of the enemy details modal. The
 * map JS reads this off the `npcCompendiumEnabled`/`npcCompendiumBaseUrl` inline options, which
 * only the real Blade render can prove are wired up correctly.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteViewNpcCompendiumFlagTest extends PublicTestCase
{
    #[Test]
    public function view_givenFeatureEnabled_exposesNpcCompendiumOptionsToMapJs(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        Feature::define(NpcCompendium::class, true);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $response->assertSee('"npcCompendiumEnabled":true', false);
            $response->assertSee(
                '"npcCompendiumBaseUrl":"' . str_replace('/', '\/', url('/compendium/npc')),
                false
            );
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function view_givenFeatureDisabled_exposesDisabledNpcCompendiumOption(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        Feature::define(NpcCompendium::class, false);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $response->assertSee('"npcCompendiumEnabled":false', false);
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
