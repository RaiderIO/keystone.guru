<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
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
    use ProvidesDungeon;

    #[Test]
    public function editFloor_givenMappingVersionWithFacadeEnabled_rendersTheFacadeToggle(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $this->be($owner);

        [$dungeon, $mappingVersion] = $this->dungeonWithFacadeEnabled(true);
        $route                      = $this->createRoute($owner, $dungeon, $mappingVersion);

        try {
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
        $owner = User::factory()->create();
        $this->be($owner);

        [$dungeon, $mappingVersion] = $this->dungeonWithFacadeEnabled(false);
        $route                      = $this->createRoute($owner, $dungeon, $mappingVersion);

        try {
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
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');

        // Switch to the admin user *before* resolving the dungeon's mapping version: it is cached
        // per acting-user game version (see Dungeon::$currentMappingVersionCache), so resolving it
        // beforehand (as an unauthenticated visitor) could hand the request below a different,
        // possibly facade-disabled mapping version than the one just verified here.
        $this->be($admin);

        [$dungeon, $mappingVersion] = $this->dungeonWithFacadeEnabled(true);
        $floor                      = $dungeon->floors()->first();

        $this->assertTrue(
            (bool)$mappingVersion->facade_enabled,
            'Mapping version under test must be facade-enabled, otherwise this test proves nothing.',
        );
        $this->assertNotNull($floor, 'Dungeon used for this test must have at least one floor.');

        // Act
        $response = $this->get(route('admin.floor.edit.mapping', [
            'dungeon'         => $dungeon,
            'floor'           => $floor,
            'mapping_version' => $mappingVersion->id,
        ]));

        // Assert
        $response->assertOk();
        $response->assertDontSee('map_controls_element_facade_toggle_btn', false);
    }

    /**
     * Finds a seeded dungeon whose *current* mapping version matches the requested facade_enabled
     * state, reusing `ProvidesDungeon`'s `inRandomOrder()`-based helpers instead of an ad-hoc
     * query. Those helpers only guarantee the presence/absence of facade floors, which - as real
     * seeded data shows - does not always agree with the current mapping version's
     * `facade_enabled` flag (older facade floors can outlive the mapping version that enabled
     * them), so this still verifies `facade_enabled` explicitly and retries otherwise.
     *
     * Must be called after switching to the acting user (`$this->be(...)`), since the resolved
     * mapping version is cached per acting-user game version and is returned here for the caller
     * to reuse verbatim, so the exact row just verified is the one the request under test uses.
     *
     * Restricted to Mythic+ dungeons (`challenge_mode_id` not null): non-Mythic+ dungeons (e.g.
     * Horrific Visions) aren't valid `DungeonRoute` targets and some don't even have a default
     * floor, which `dungeonroute.edit`'s redirect chain requires.
     *
     * @return array{0: Dungeon, 1: MappingVersion}
     */
    private function dungeonWithFacadeEnabled(bool $facadeEnabled): array
    {
        $constraint = static fn(Builder $query): Builder => $query->whereNotNull('challenge_mode_id');

        $count = 0;
        do {
            if (++$count > 20) {
                self::fail(sprintf(
                    'No seeded dungeon with a facade_enabled=%s mapping version found to test the edit sidebar.',
                    $facadeEnabled ? 'true' : 'false',
                ));
            }

            $dungeon = $facadeEnabled
                ? $this->getDungeonWithFacadeFloor($constraint)
                : $this->getDungeonWithNonFacadeFloor($constraint);
            $mappingVersion = $dungeon->getCurrentMappingVersion();
        } while ($mappingVersion === null || (bool)$mappingVersion->facade_enabled !== $facadeEnabled);

        return [$dungeon, $mappingVersion];
    }

    /**
     * A published, non-sandbox route owned by $owner, pinned to $mappingVersion. Sandbox routes
     * (which the factory creates by default) are editable by anyone, so expires_at must be null
     * here.
     */
    private function createRoute(User $owner, Dungeon $dungeon, MappingVersion $mappingVersion): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
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
