<?php

namespace Tests\Feature\Controller\Ajax;

use App\Features\CreatorProfiles;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\DungeonRoute\DungeonRouteCollectionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The endpoint offered by the "make them visible too" confirmation after a collection's own published state was
 * raised: raises every route of the collection that is still less visible than it, computed and authorized
 * server-side.
 */
#[Group('Controller')]
#[Group('DungeonRouteCollection')]
final class AjaxDungeonRouteCollectionRoutesPublishTest extends PublicTestCase
{
    /** @var array<int, User> */
    private array $createdUsers = [];

    /** @var array<int, DungeonRoute> */
    private array $createdDungeonRoutes = [];

    /** @var array<int, MappingVersion> */
    private array $createdMappingVersions = [];

    /** @var array<int, DungeonRouteCollection> */
    private array $createdDungeonRouteCollections = [];

    protected function setUp(): void
    {
        parent::setUp();

        Feature::for(null)->activate(CreatorProfiles::class);

        $this->beforeApplicationDestroyed(function (): void {
            foreach ($this->createdDungeonRouteCollections as $dungeonRouteCollection) {
                $dungeonRouteCollection->delete();
            }

            foreach ($this->createdDungeonRoutes as $dungeonRoute) {
                $dungeonRoute->delete();
            }

            foreach ($this->createdMappingVersions as $mappingVersion) {
                $mappingVersion->delete();
            }

            foreach ($this->createdUsers as $user) {
                Feature::for($user)->forget(CreatorProfiles::class);
                $user->delete();
            }

            Feature::for(null)->forget(CreatorProfiles::class);
        });
    }

    #[Test]
    public function publishRoutes_givenAGuest_returnsUnauthorized(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD, [$dungeonRoute]);

        // Act
        $response = $this->publish(null, $dungeonRouteCollection);

        // Assert
        $response->assertUnauthorized();
        $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $dungeonRoute->fresh()->published_state_id);
    }

    #[Test]
    public function publishRoutes_givenAUserWhoDoesNotOwnTheCollection_returnsForbidden(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $otherUser              = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD, [$dungeonRoute]);

        // Act
        $response = $this->publish($otherUser, $dungeonRouteCollection);

        // Assert
        $response->assertForbidden();
        $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $dungeonRoute->fresh()->published_state_id);
    }

    #[Test]
    public function publishRoutes_givenRoutesBelowTheCollectionsState_raisesThemAndLeavesOthersUntouched(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $unpublished            = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $team                   = $this->createRoute($owner, PublishedState::TEAM);
        $alreadyWorld           = $this->createRoute($owner, PublishedState::WORLD);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD, [
            $unpublished,
            $team,
            $alreadyWorld,
        ]);

        // Act
        $beforePublish = Carbon::now()->subSecond();
        $response      = $this->publish($owner, $dungeonRouteCollection);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('raised_count', 2);
        $response->assertJsonPath('skipped_count', 0);
        $this->assertSame(PublishedState::ALL[PublishedState::WORLD], $unpublished->fresh()->published_state_id);
        $this->assertSame(PublishedState::ALL[PublishedState::WORLD], $team->fresh()->published_state_id);
        $this->assertTrue($unpublished->fresh()->published_at->greaterThanOrEqualTo($beforePublish));
    }

    #[Test]
    public function publishRoutes_givenARouteOwnedBySomeoneElse_skipsItAndReportsTheSkip(): void
    {
        // Arrange - an admin editing someone else's collection may contain routes of other authors too
        $owner                  = $this->createUser();
        $otherAuthor            = $this->createUser();
        $ownRoute               = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $foreignRoute           = $this->createRoute($otherAuthor, PublishedState::UNPUBLISHED);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD, [
            $ownRoute,
            $foreignRoute,
        ]);

        // Act
        $response = $this->publish($owner, $dungeonRouteCollection);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('raised_count', 1);
        $response->assertJsonPath('skipped_count', 1);
        $this->assertSame(PublishedState::ALL[PublishedState::WORLD], $ownRoute->fresh()->published_state_id);
        $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $foreignRoute->fresh()->published_state_id);
    }

    #[Test]
    public function publishRoutes_givenRoutesWithoutATeamId_raisesThemWithoutLazyLoading(): void
    {
        // Arrange: Eloquent only flags lazy loading on models hydrated as part of a multi-model result
        $owner                  = $this->createUser();
        $firstDungeonRoute      = $this->createRoute($owner, PublishedState::UNPUBLISHED, null);
        $secondDungeonRoute     = $this->createRoute($owner, PublishedState::UNPUBLISHED, null);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD, [$firstDungeonRoute, $secondDungeonRoute]);

        // Act
        $response = $this->publish($owner, $dungeonRouteCollection);

        // Assert
        $response->assertOk();
        $response->assertJson(['raised_count' => 2, 'skipped_count' => 0]);
        $this->assertSame(PublishedState::ALL[PublishedState::WORLD], $firstDungeonRoute->fresh()->published_state_id);
        $this->assertSame(PublishedState::ALL[PublishedState::WORLD], $secondDungeonRoute->fresh()->published_state_id);
    }

    #[Test]
    public function publishRoutes_givenAnUnlistedCollectionAndNoUnlistedRoutesBenefit_skipsTheRoutes(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD_WITH_LINK, [$dungeonRoute]);

        // Act
        $response = $this->publish($owner, $dungeonRouteCollection);

        // Assert
        $response->assertOk();
        $response->assertJson(['raised_count' => 0, 'skipped_count' => 1]);
        $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $dungeonRoute->fresh()->published_state_id);
    }

    #[Test]
    public function filterRoutesRaisableToCollection_givenARouteInAnInactiveDungeon_leavesItOutOfAWorldCollection(): void
    {
        // Arrange: the seeded test data holds no inactive dungeon, so one route's dungeon is flagged inactive in memory
        $owner                  = $this->createUser();
        $activeRoute            = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $inactiveRoute          = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD, [$activeRoute, $inactiveRoute]);

        $inactiveDungeon         = clone $inactiveRoute->dungeon;
        $inactiveDungeon->active = false;
        $inactiveRoute->setRelation('dungeon', $inactiveDungeon);

        // Act
        $raisableDungeonRoutes = app(DungeonRouteCollectionServiceInterface::class)->filterRoutesRaisableToCollection(
            $dungeonRouteCollection,
            collect([$activeRoute, $inactiveRoute]),
            $owner,
        );

        // Assert
        $this->assertSame([$activeRoute->id], $raisableDungeonRoutes->pluck('id')->all());
    }

    #[Test]
    public function publishRoutes_givenATeamCollectionAndARouteOutsideItsTeam_skipsTheRoute(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::TEAM, [$dungeonRoute]);

        // Act
        $response = $this->publish($owner, $dungeonRouteCollection);

        // Assert
        $response->assertOk();
        $response->assertJson(['raised_count' => 0, 'skipped_count' => 1]);
        $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $dungeonRoute->fresh()->published_state_id);
    }

    #[Test]
    public function publishRoutes_givenNoRouteIsLessVisibleThanTheCollection_raisesNone(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $alreadyWorld           = $this->createRoute($owner, PublishedState::WORLD);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD, [$alreadyWorld]);

        // Act
        $response = $this->publish($owner, $dungeonRouteCollection);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('raised_count', 0);
        $response->assertJsonPath('skipped_count', 0);
        $this->assertSame(PublishedState::ALL[PublishedState::WORLD], $alreadyWorld->fresh()->published_state_id);
    }

    #[Test]
    public function publishRoutes_givenAnAdminOnSomeoneElsesCollection_raisesTheOwnersRoutes(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $admin                  = User::findOrFail(1);
        $unpublished            = $this->createRoute($owner, PublishedState::UNPUBLISHED);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, PublishedState::WORLD, [$unpublished]);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        Feature::for($admin)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->publish($admin, $dungeonRouteCollection);

            // Assert
            $response->assertOk();
            $response->assertJsonPath('raised_count', 1);
            $this->assertSame(PublishedState::ALL[PublishedState::WORLD], $unpublished->fresh()->published_state_id);
        } finally {
            Feature::for($admin)->forget(CreatorProfiles::class);
        }
    }

    /**
     * @return TestResponse<JsonResponse>
     */
    private function publish(?User $user, DungeonRouteCollection $dungeonRouteCollection): TestResponse
    {
        $request = $this->withHeader('X-Requested-With', 'XMLHttpRequest');
        if ($user !== null) {
            $request = $request->actingAs($user);
        }

        return $request->postJson(route('ajax.collection.routes.publish', ['dungeonRouteCollection' => $dungeonRouteCollection]));
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);
        Feature::for($user)->activate(CreatorProfiles::class);
        $this->createdUsers[] = $user;

        return $user;
    }

    /**
     * A route on a mapping version of its own holding no enemies at all, so hasKilledAllRequiredEnemies() always
     * passes regardless of which dungeon the factory happens to pick.
     */
    private function createRoute(User $author, string $publishedState, ?int $teamId = -1): DungeonRoute
    {
        // Only an active dungeon may be made public, and the factory would otherwise pick any dungeon at random
        $dungeon = Dungeon::query()
            ->where('active', true)
            ->whereNotNull('challenge_mode_id')
            ->whereHas('floors')
            ->get()
            ->first(static fn(Dungeon $dungeon): bool => $dungeon->getCurrentMappingVersion() !== null);

        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => $author->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            // -1 makes dungeonRouteChanged() skip its own change log entirely; null (the factory default) does not
            'team_id'            => $teamId,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ]);
        $this->createdDungeonRoutes[] = $dungeonRoute;

        $current = $dungeonRoute->mappingVersion;
        $now     = Carbon::now()->toDateTimeString();

        // Inserted quietly, as MappingService::copyMappingVersionToDungeon() does - the model's mutators would
        // otherwise touch fields this test does not care about
        $mappingVersion = MappingVersion::findOrFail(MappingVersion::insertGetId([
            'game_version_id'                 => $current->game_version_id,
            'dungeon_id'                      => $dungeonRoute->dungeon_id,
            'version'                         => $current->version + 1,
            'enemy_forces_required'           => $current->enemy_forces_required,
            'enemy_forces_required_teeming'   => $current->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $current->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $current->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $current->timer_max_seconds,
            'created_at'                      => $now,
            'updated_at'                      => $now,
        ]));
        $this->createdMappingVersions[] = $mappingVersion;

        $dungeonRoute->update(['mapping_version_id' => $mappingVersion->id, 'teeming' => false]);

        return $dungeonRoute;
    }

    /**
     * @param array<int, DungeonRoute> $dungeonRoutes
     */
    private function createFreeFormCollection(User $owner, string $publishedState, array $dungeonRoutes): DungeonRouteCollection
    {
        $dungeonRouteCollection = DungeonRouteCollection::factory()->freeForm($this->retail())->create([
            'user_id'            => $owner->id,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ]);
        $this->createdDungeonRouteCollections[] = $dungeonRouteCollection;

        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        return $dungeonRouteCollection;
    }

    private function retail(): GameVersion
    {
        return GameVersion::query()->findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]);
    }
}
