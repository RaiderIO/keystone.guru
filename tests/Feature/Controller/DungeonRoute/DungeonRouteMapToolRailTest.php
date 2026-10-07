<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\LiveSession\LiveSession;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The map's left tool rail is laid out in CSS, which the server cannot see; these tests pin the two
 * hooks that layout depends on: the ad_loaded class that keeps the rail clear of the bottom rail ad,
 * and the fixed Popper strategy that lets the rail's flyout menus escape its scroll container.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteMapToolRailTest extends PublicTestCase
{
    #[Test]
    public function view_givenUserWithAds_marksTheToolRailAdLoaded(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->routeUrl('dungeonroute.view', $route));

            // Assert
            $response->assertOk();
            $this->assertContains('ad_loaded', $this->toolRailClasses((string)$response->getContent()));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function view_givenAdFreeUser_leavesTheToolRailUnmarked(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $giver    = User::factory()->create();
        $route    = $this->createRoute($owner);
        $giveaway = PatreonAdFreeGiveaway::create([
            'giver_user_id'    => $giver->id,
            'receiver_user_id' => $owner->id,
        ]);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->routeUrl('dungeonroute.view', $route));

            // Assert
            $response->assertOk();
            $classes = $this->toolRailClasses((string)$response->getContent());
            $this->assertContains('route_manipulation_tools', $classes);
            $this->assertNotContains('ad_loaded', $classes);
        } finally {
            $giveaway->delete();
            $route->delete();
            $giver->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function edit_givenUserWithAds_marksTheToolRailAdLoaded(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->routeUrl('dungeonroute.edit', $route));

            // Assert
            $response->assertOk();
            $this->assertContains('ad_loaded', $this->toolRailClasses((string)$response->getContent()));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function liveSession_givenUserWithAds_marksTheToolRailAdLoaded(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $owner->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $route       = $this->createRoute($owner);
        $liveSession = LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => $owner->id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get(route('dungeonroute.livesession.view', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'liveSession'  => $liveSession,
            ]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('liveSession', static fn(LiveSession $renderedLiveSession) => $renderedLiveSession->id === $liveSession->id);
            $this->assertContains('ad_loaded', $this->toolRailClasses((string)$response->getContent()));
        } finally {
            // Mass delete: LiveSession's "deleting" hook cascades into overpulled_enemies, a table no migration creates
            LiveSession::query()->whereKey($liveSession->id)->delete();
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function view_givenRoute_rendersEveryToolRailFlyoutWithAFixedPopperStrategy(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->routeUrl('dungeonroute.view', $route));

            // Assert
            $response->assertOk();
            $toolRail = $this->toolRailHtml((string)$response->getContent());
            preg_match_all('/<button[^>]*data-bs-toggle="dropdown"[^>]*>/', $toolRail, $toggles);
            // Floor switch, enemy display type, map object group visibility and rating
            $this->assertCount(4, $toggles[0]);
            foreach ($toggles[0] as $toggle) {
                $this->assertStringContainsString('data-bs-boundary="viewport"', $toggle);
                $this->assertStringContainsString('data-bs-popper-config=\'{"strategy":"fixed"}\'', $toggle);
            }
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * @return array<int, string>
     */
    private function toolRailClasses(string $content): array
    {
        $matched = preg_match('/<nav\s+class="([^"]*\broute_manipulation_tools\b[^"]*)"/', $content, $matches);
        $this->assertSame(1, $matched, 'The page must render the map tool rail.');

        return preg_split('/\s+/', trim($matches[1]));
    }

    private function toolRailHtml(string $content): string
    {
        $matched = preg_match('/<nav\s+class="[^"]*\broute_manipulation_tools\b.*?<\/nav>/s', $content, $matches);
        $this->assertSame(1, $matched, 'The page must render the map tool rail.');

        return $matches[0];
    }

    /**
     * A published, non-sandbox route owned by $owner, so its owner may open the edit page.
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
     * Both pages redirect to the default floor, so tests follow redirects.
     */
    private function routeUrl(string $name, DungeonRoute $route): string
    {
        return route($name, [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
