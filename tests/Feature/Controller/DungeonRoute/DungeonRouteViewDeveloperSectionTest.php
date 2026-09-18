<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The route admin settings are reached through the site header's Developer section, which only admins see.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteViewDeveloperSectionTest extends PublicTestCase
{
    #[Test]
    public function view_givenAnAdmin_rendersTheDeveloperSectionWithTheRouteEntries(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            $this->be($admin);

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('id="navCategoryDeveloper"', $html);
            $this->assertStringContainsString('data-modal-tab="#dungeon_route_info_tab"', $html);
            $this->assertStringContainsString('data-modal-tab="#combatlog_info_tab"', $html);
            $this->assertStringContainsString('id="edit_route_admin_settings_modal"', $html);
            $this->assertStringContainsString(e(route('admin.floor.edit.mapping', [
                'dungeon'         => $route->dungeon,
                'floor'           => $route->dungeon->floors->first(),
                'mapping_version' => $route->mapping_version_id,
            ])), $html);
            $this->assertStringNotContainsString('edit_route_admin_settings_button', $html);

            $developerPosition   = strpos($html, 'id="navCategoryDeveloper"');
            $createRoutePosition = strpos($html, 'data-bs-target="#create_route_modal"', (int)$developerPosition);
            $this->assertNotFalse($createRoutePosition, 'The Create route button must follow the Developer section.');
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function view_givenANonAdmin_omitsTheDeveloperSection(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonAdmin = User::factory()->create();
        $route    = $this->createRoute($owner);

        try {
            $this->be($nonAdmin);

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringNotContainsString('id="navCategoryDeveloper"', $html);
            $this->assertStringNotContainsString('edit_route_admin_settings_modal', $html);
        } finally {
            $route->delete();
            $owner->delete();
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function view_givenAGuest_omitsTheDeveloperSection(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            $this->actingAsGuest();

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringNotContainsString('id="navCategoryDeveloper"', $html);
            $this->assertStringNotContainsString('edit_route_admin_settings_modal', $html);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

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
