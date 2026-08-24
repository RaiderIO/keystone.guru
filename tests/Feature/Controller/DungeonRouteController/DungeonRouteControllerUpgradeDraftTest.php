<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\DungeonRoute\DungeonRouteUpgradeDraftServiceInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
class DungeonRouteControllerUpgradeDraftTest extends PublicTestCase
{
    use ProvidesDungeon;

    /**
     * Every model created by a test, torn down newest first.
     *
     * @var array<int, Model>
     */
    private array $cleanup = [];

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        $this->cleanup[] = $user;

        return $user;
    }

    /**
     * Creates a route on a mapping version that is no longer its dungeon's current one, which is the
     * state that offers the author the Upgrade button.
     */
    private function createOutdatedRoute(User $owner): DungeonRoute
    {
        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        $newMappingVersion = MappingVersion::create([
            'game_version_id'                 => $mappingVersion->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $mappingVersion->version + 1000,
            'enemy_forces_required'           => $mappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $mappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $mappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $mappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $mappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);
        $this->cleanup[] = $newMappingVersion;

        $route = DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        array_unshift($this->cleanup, $route);

        return $route;
    }

    private function tearDownCleanup(): void
    {
        foreach ($this->cleanup as $model) {
            $model->refresh();
            $model->delete();
        }
        $this->cleanup = [];
    }

    #[Test]
    public function upgrade_givenOutdatedRoute_redirectsToDraftEditPage(): void
    {
        try {
            // Arrange
            $owner = $this->createUser();
            $route = $this->createOutdatedRoute($owner);

            // Act
            $response = $this->actingAs($owner)->get(route('dungeonroute.upgrade', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));

            // Assert
            $draft = DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $route->id)->firstOrFail();
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function upgrade_givenRouteWithExistingDraft_redirectsToExistingDraft(): void
    {
        try {
            // Arrange
            $owner = $this->createUser();
            $route = $this->createOutdatedRoute($owner);

            $this->actingAs($owner)->get(route('dungeonroute.upgrade', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));
            $firstDraft = DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $route->id)->firstOrFail();

            // Act
            $this->actingAs($owner)->get(route('dungeonroute.upgrade', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route->refresh(),
                'title'        => $route->getTitleSlug(),
            ]));

            // Assert
            $this->assertSame(1, DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $route->id)->count());
            $this->assertNotNull(DungeonRoute::find($firstDraft->id));
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function upgrade_givenDraftRoute_redirectsToDraftWithWarningInsteadOfThrowing(): void
    {
        try {
            // Arrange - a draft cannot itself have an upgrade draft (findOrCreateDraft() refuses this),
            // but the URL is not gated on is_upgrade_draft, only on edit rights - so a direct hit must
            // still land gracefully rather than 500
            $owner = $this->createUser();
            $route = $this->createOutdatedRoute($owner);

            $this->actingAs($owner)->get(route('dungeonroute.upgrade', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));
            $draft = DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $route->id)->firstOrFail();

            // Act
            $response = $this->actingAs($owner)->get(route('dungeonroute.upgrade', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));

            // Assert
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));
            $response->assertSessionHas('warning');
            $this->assertNotNull(DungeonRoute::find($draft->id), 'The draft itself must not be deleted or mutated');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function applyUpgrade_givenDraft_redirectsToOriginalEditPage(): void
    {
        try {
            // Arrange
            $owner = $this->createUser();
            $route = $this->createOutdatedRoute($owner);
            $this->actingAs($owner)->get(route('dungeonroute.upgrade', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));
            $draft = DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $route->id)->firstOrFail();

            // Act
            $response = $this->actingAs($owner)->post(route('dungeonroute.upgrade.apply', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));

            // Assert
            $original = DungeonRoute::findOrFail($route->id);
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $original->dungeon,
                'dungeonroute' => $original,
                'title'        => $original->getTitleSlug(),
            ]));
            $this->assertNull(DungeonRoute::find($draft->id), 'Apply deletes the draft');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function applyUpgrade_givenDraftMissingRequiredEnemy_redirectsToDraftWithWarningAndDoesNotDeleteDraft(): void
    {
        $newMappingVersionId = null;

        try {
            // Arrange
            $owner = $this->createUser();
            $route = $this->createOutdatedRoute($owner);
            $this->actingAs($owner)->get(route('dungeonroute.upgrade', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));
            $draft               = DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $route->id)->firstOrFail();
            $newMappingVersionId = $draft->mapping_version_id;

            Enemy::create([
                'mapping_version_id' => $newMappingVersionId,
                'floor_id'           => $draft->dungeon->floors->first()->id,
                'npc_id'             => null,
                'teeming'            => null,
                'required'           => true,
                'lat'                => 0,
                'lng'                => 0,
            ]);

            // Act
            $response = $this->actingAs($owner)->post(route('dungeonroute.upgrade.apply', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));

            // Assert
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));
            $response->assertSessionHas('warning');
            $this->assertNotNull(DungeonRoute::find($draft->id), 'A rejected Apply must not delete the draft');
            $this->assertSame(
                PublishedState::ALL[PublishedState::WORLD],
                DungeonRoute::findOrFail($route->id)->published_state_id,
                'A rejected Apply must not mutate the original',
            );
        } finally {
            if ($newMappingVersionId !== null) {
                Enemy::query()->where('mapping_version_id', $newMappingVersionId)->delete();
            }
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function applyUpgrade_givenUnauthenticatedUser_returnsRedirectToLogin(): void
    {
        try {
            // Arrange
            $owner = $this->createUser();
            $route = $this->createOutdatedRoute($owner);
            // Deliberately NOT through the upgrade endpoint: actingAs() persists for the rest of the
            // test, which would leave this request authenticated after all
            $draft = app(DungeonRouteUpgradeDraftServiceInterface::class)->findOrCreateDraft($route);

            // Act
            $response = $this->post(route('dungeonroute.upgrade.apply', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));

            // Assert
            $response->assertRedirect(route('login'));
            $this->assertNotNull(DungeonRoute::find($draft->id), 'The draft must survive an unauthenticated attempt');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function discardUpgrade_givenDraft_redirectsToOriginalEditPage(): void
    {
        try {
            // Arrange
            $owner = $this->createUser();
            $route = $this->createOutdatedRoute($owner);
            $this->actingAs($owner)->get(route('dungeonroute.upgrade', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));
            $draft = DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $route->id)->firstOrFail();

            // Act
            $response = $this->actingAs($owner)->post(route('dungeonroute.upgrade.discard', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));

            // Assert
            $original = DungeonRoute::findOrFail($route->id);
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $original->dungeon,
                'dungeonroute' => $original,
                'title'        => $original->getTitleSlug(),
            ]));
            $this->assertNull(DungeonRoute::find($draft->id));
        } finally {
            $this->tearDownCleanup();
        }
    }
}
