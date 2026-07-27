<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Covers issue #3704 - the route edit page must show the Blizzard/MDT floor-style (facade) toggle
 * whenever the route's mapping version supports it, mirroring the route view page's sidebar. The
 * admin mapping-version editor renders through the same `common.maps.controls.draw` partial but
 * must never show the toggle (per the issue's explicit scope), so that page is covered too.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteEditSidebarTest extends PublicTestCase
{
    #[Test]
    public function editFloor_givenMappingVersionWithFacadeEnabled_rendersTheFacadeToggle(): void
    {
        // Arrange
        $owner   = User::factory()->create();
        $dungeon = $this->dungeonWithFacadeEnabled(true);
        $route   = $this->createRoute($owner, $dungeon);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->editUrl($route));

            // Assert
            $response->assertOk();
            $response->assertSee('map_controls_element_facade_toggle_btn', false);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function editFloor_givenMappingVersionWithFacadeDisabled_hidesTheFacadeToggle(): void
    {
        // Arrange
        $owner   = User::factory()->create();
        $dungeon = $this->dungeonWithFacadeEnabled(false);
        $route   = $this->createRoute($owner, $dungeon);

        try {
            $this->be($owner);

            // Act
            $response = $this->followingRedirects()->get($this->editUrl($route));

            // Assert
            $response->assertOk();
            $response->assertDontSee('map_controls_element_facade_toggle_btn', false);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function mapping_givenMappingVersionWithFacadeEnabled_hidesTheFacadeToggle(): void
    {
        // Arrange
        $admin   = User::findOrFail(1);
        $dungeon = $this->dungeonWithFacadeEnabled(true);
        $floor   = $dungeon->floors()->first();

        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $this->assertNotNull($floor, 'Dungeon used for this test must have at least one floor.');

        $this->be($admin);

        // Act
        $response = $this->get(route('admin.floor.edit.mapping', [
            'dungeon'         => $dungeon,
            'floor'           => $floor,
            'mapping_version' => $dungeon->getCurrentMappingVersion()->id,
        ]));

        // Assert
        $response->assertOk();
        $response->assertDontSee('map_controls_element_facade_toggle_btn', false);
    }

    /**
     * Finds a seeded dungeon whose current mapping version matches the requested facade_enabled
     * state, so the test exercises real data instead of hand-crafting a MappingVersion row.
     */
    private function dungeonWithFacadeEnabled(bool $facadeEnabled): Dungeon
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->get()
            ->first(static function (Dungeon $dungeon) use ($facadeEnabled): bool {
                $mappingVersion = $dungeon->getCurrentMappingVersion();

                return $mappingVersion !== null && (bool)$mappingVersion->facade_enabled === $facadeEnabled;
            });

        if ($dungeon === null) {
            self::fail(sprintf(
                'No seeded dungeon with facade_enabled=%s found to test the edit sidebar.',
                $facadeEnabled ? 'true' : 'false',
            ));
        }

        return $dungeon;
    }

    /**
     * A published, non-sandbox route owned by $owner, pinned to $dungeon's current mapping
     * version. Sandbox routes (which the factory creates by default) are editable by anyone, so
     * expires_at must be null here.
     */
    private function createRoute(User $owner, Dungeon $dungeon): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    private function editUrl(DungeonRoute $route): string
    {
        return route('dungeonroute.edit', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
