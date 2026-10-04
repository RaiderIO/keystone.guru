<?php

namespace Tests\Feature\View\Common\Maps;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * The pull-cycling keyboard hint sits right-aligned after the enemy forces counter, away from the
 * settings button, in both the view and edit layouts of the pulls sidebar.
 */
#[Group('View')]
#[Group('PullsSidebar')]
final class PullsSidebarKeyboardHintTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string SETTINGS_BUTTON      = 'fa-cog';
    private const string ENEMY_FORCES_COUNTER = 'id="edit_route_enemy_forces_container"';
    private const string KEYBOARD_HINT        = 'fa-keyboard';

    #[Test]
    public function view_givenRouteWithEnemyForces_placesKeyboardHintAfterEnemyForcesCounter(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routeUrl('dungeonroute.view', $route));

            // Assert
            $this->assertKeyboardHintFollowsEnemyForcesCounter($response);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function edit_givenRouteWithEnemyForces_placesKeyboardHintAfterEnemyForcesCounter(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routeUrl('dungeonroute.edit', $route));

            // Assert
            $this->assertKeyboardHintFollowsEnemyForcesCounter($response);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * @param TestResponse<Response> $response
     */
    private function assertKeyboardHintFollowsEnemyForcesCounter(TestResponse $response): void
    {
        $response->assertOk();

        $html  = $response->getContent();
        $start = strpos($html, '<nav id="pulls_sidebar"');
        $this->assertNotFalse($start, 'The pulls sidebar was not rendered');
        $sidebar = substr($html, $start, strpos($html, '</nav>', $start) - $start);

        $settingsPosition     = strpos($sidebar, self::SETTINGS_BUTTON);
        $enemyForcesPosition  = strpos($sidebar, self::ENEMY_FORCES_COUNTER);
        $keyboardHintPosition = strpos($sidebar, self::KEYBOARD_HINT);

        $this->assertNotFalse($settingsPosition, 'The settings button was not rendered');
        $this->assertNotFalse($enemyForcesPosition, 'The enemy forces counter was not rendered');
        $this->assertNotFalse($keyboardHintPosition, 'The keyboard hint was not rendered');
        $this->assertSame(1, substr_count($sidebar, self::KEYBOARD_HINT));
        $this->assertLessThan($enemyForcesPosition, $settingsPosition);
        $this->assertGreaterThan($enemyForcesPosition, $keyboardHintPosition);
    }

    /**
     * A published route on a dungeon that counts enemy forces, so both layouts render the counter.
     */
    private function createRoute(User $owner): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, speedrunEnabled: false, dungeonActive: true, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    private function routeUrl(string $routeName, DungeonRoute $route): string
    {
        return route($routeName, [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
