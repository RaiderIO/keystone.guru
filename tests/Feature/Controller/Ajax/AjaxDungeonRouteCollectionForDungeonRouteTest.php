<?php

namespace Tests\Feature\Controller\Ajax;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\User;
use App\Service\DungeonRoute\DungeonRouteCollectionServiceInterface;
use Database\Factories\DungeonRoute\DungeonRouteCollectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

/**
 * The collections the "Add to collection…" dialog lists for one of the user's own routes, and whether the route may
 * join each of them.
 */
#[Group('Controller')]
#[Group('DungeonRouteCollection')]
final class AjaxDungeonRouteCollectionForDungeonRouteTest extends PublicTestCase
{
    use CreatesSeason;

    /** @var array<int, User> */
    private array $createdUsers = [];

    /** @var array<int, DungeonRoute> */
    private array $createdDungeonRoutes = [];

    protected function setUp(): void
    {
        parent::setUp();

        Feature::for(null)->activate(CreatorProfiles::class);

        $this->beforeApplicationDestroyed(function (): void {
            foreach ($this->createdUsers as $user) {
                DungeonRouteCollection::query()
                    ->where('user_id', $user->id)
                    ->get()
                    ->each(static fn(DungeonRouteCollection $dungeonRouteCollection) => $dungeonRouteCollection->delete());
            }

            foreach ($this->createdDungeonRoutes as $dungeonRoute) {
                $dungeonRoute->delete();
            }

            foreach ($this->createdUsers as $user) {
                Feature::for($user)->forget(CreatorProfiles::class);
                $user->delete();
            }

            Feature::for(null)->forget(CreatorProfiles::class);
        });
    }

    #[Test]
    public function forDungeonRoute_givenAGuest_returnsUnauthorized(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());

        // Act
        $response = $this->request($dungeonRoute);

        // Assert
        $response->assertUnauthorized();
    }

    #[Test]
    public function forDungeonRoute_givenSomeoneElsesRoute_failsValidation(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $otherUser    = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());
        $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $otherUser);

        // Act
        $response = $this->actingAs($otherUser)->request($dungeonRoute);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_route']);
        $response->assertJsonMissingPath('collections');
    }

    #[Test]
    public function forDungeonRoute_givenASandboxRoute_failsValidation(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion(), null, ['expires_at' => now()->addDay()]);

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_route']);
    }

    #[Test]
    public function forDungeonRoute_givenTheFeatureIsInactive_returnsNotFound(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());
        Feature::for($owner)->deactivate(CreatorProfiles::class);

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function forDungeonRoute_givenNoCollections_returnsOnlyTheNewCollectionEntry(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('collections', []);
        $response->assertJsonPath('may_create', true);
        $response->assertJsonPath('create_url', route('collections.new', ['dungeon_route' => $dungeonRoute->public_key]));
    }

    #[Test]
    public function forDungeonRoute_givenACollectionWhoseDungeonIsFull_reportsDungeonFull(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createCollection(
            DungeonRouteCollection::factory()->freeForm($this->retail()),
            $owner,
            $this->createRoutes($owner, DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON),
        );

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertOk();
        $rows = collect($this->collectionsOf($response))->keyBy('public_key');
        $this->assertSame(
            DungeonRouteCollectionServiceInterface::ADD_BLOCKED_DUNGEON_FULL,
            $rows[$dungeonRouteCollection->public_key]['blocked_reason'],
        );
        $this->assertSame(DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON, $rows[$dungeonRouteCollection->public_key]['same_dungeon_route_count']);
        $this->assertSame(DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON, $rows[$dungeonRouteCollection->public_key]['max_routes_per_dungeon']);
    }

    #[Test]
    public function forDungeonRoute_givenCollectionsOfEveryKind_reportsMembershipAndWhyTheRouteCannotJoin(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $season       = $this->createRetailSeason();
        $otherSeason  = $this->createRetailSeason();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        $filler       = $this->createRoute($owner, $this->retailMappingVersion(), $season);

        $member       = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner, [$dungeonRoute]);
        $eligible     = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season), $owner, [$filler]);
        $wrongSeason  = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($otherSeason), $owner);
        $wrongVersion = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->gameVersionWithoutSeasons()), $owner);
        $full         = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner, $this->createRoutes($owner, DungeonRouteCollection::MAX_ROUTES));

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertOk();
        $rows = collect($this->collectionsOf($response))->keyBy('public_key');
        $this->assertCount(5, $rows);

        $this->assertTrue($rows[$member->public_key]['contains_dungeon_route']);
        $this->assertNull($rows[$member->public_key]['blocked_reason']);
        $this->assertSame(1, $rows[$member->public_key]['route_count']);

        $this->assertFalse($rows[$eligible->public_key]['contains_dungeon_route']);
        $this->assertNull($rows[$eligible->public_key]['blocked_reason']);
        $this->assertSame(DungeonRouteCollection::MAX_ROUTES, $rows[$eligible->public_key]['max_routes']);
        $this->assertSame(route('ajax.collection.routes.store', ['dungeonRouteCollection' => $eligible]), $rows[$eligible->public_key]['store_url']);

        $this->assertSame(DungeonRouteCollectionServiceInterface::ADD_BLOCKED_SEASON, $rows[$wrongSeason->public_key]['blocked_reason']);
        $this->assertSame($otherSeason->name_long, $rows[$wrongSeason->public_key]['season']['name_long']);
        $this->assertSame(DungeonRouteCollectionServiceInterface::ADD_BLOCKED_GAME_VERSION, $rows[$wrongVersion->public_key]['blocked_reason']);
        $this->assertSame(DungeonRouteCollectionServiceInterface::ADD_BLOCKED_FULL, $rows[$full->public_key]['blocked_reason']);
        $this->assertSame(DungeonRouteCollection::MAX_ROUTES, $rows[$full->public_key]['route_count']);

        // Collections the route may be toggled in come before the ones it cannot join
        $order = collect($this->collectionsOf($response))->pluck('public_key');
        $this->assertEqualsCanonicalizing([$member->public_key, $eligible->public_key], $order->take(2)->all());
    }

    #[Test]
    public function forDungeonRoute_givenASeasonSetAndAFreeFormCollection_returnsWhatEachCovers(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $season       = $this->createRetailSeason();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        $seasonSet    = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season), $owner, [$dungeonRoute]);
        $freeForm     = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertOk();
        $rows = collect($this->collectionsOf($response))->keyBy('public_key');

        $this->assertSame($season->name, $rows[$seasonSet->public_key]['season']['name']);
        $this->assertSame($season->name_long, $rows[$seasonSet->public_key]['season']['name_long']);
        $this->assertSame($season->dungeons()->count(), $rows[$seasonSet->public_key]['season']['dungeon_count']);
        $this->assertSame(1, $rows[$seasonSet->public_key]['covered_dungeon_count']);
        $this->assertSame($this->retail()->name, $rows[$seasonSet->public_key]['game_version']);

        $this->assertNull($rows[$freeForm->public_key]['season']);
        $this->assertSame(0, $rows[$freeForm->public_key]['covered_dungeon_count']);
    }

    #[Test]
    public function forDungeonRoute_givenAFullCollectionThatHoldsTheRoute_letsItBeRemoved(): void
    {
        // Arrange
        $owner         = $this->createUser();
        $dungeonRoutes = $this->createRoutes($owner, DungeonRouteCollection::MAX_ROUTES);
        $full          = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner, $dungeonRoutes);

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoutes[0]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('collections.0.public_key', $full->public_key);
        $response->assertJsonPath('collections.0.contains_dungeon_route', true);
        $response->assertJsonPath('collections.0.blocked_reason', null);
    }

    #[Test]
    public function forDungeonRoute_givenARouteWithoutAMappingVersion_reportsTheGameVersionForEveryCollection(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion(), null, ['mapping_version_id' => null]);
        $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('collections.0.blocked_reason', DungeonRouteCollectionServiceInterface::ADD_BLOCKED_GAME_VERSION);
    }

    #[Test]
    public function forDungeonRoute_givenAnotherUsersCollections_leavesThemOut(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $otherUser    = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());
        $own          = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);
        $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $otherUser);

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertOk();
        $this->assertSame([$own->public_key], collect($this->collectionsOf($response))->pluck('public_key')->all());
    }

    #[Test]
    public function forDungeonRoute_givenTheCollectionCap_reportsThatNoCollectionMayBeCreated(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());
        for ($i = 0; $i < DungeonRouteCollection::MAX_COLLECTIONS; $i++) {
            $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);
        }

        // Act
        $response = $this->actingAs($owner)->request($dungeonRoute);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('collection_count', DungeonRouteCollection::MAX_COLLECTIONS);
        $response->assertJsonPath('max_collections', DungeonRouteCollection::MAX_COLLECTIONS);
        $response->assertJsonPath('may_create', false);
    }

    /**
     * @param  TestResponse<JsonResponse>       $response
     * @return array<int, array<string, mixed>>
     */
    private function collectionsOf(TestResponse $response): array
    {
        /** @var array<int, array<string, mixed>> $collections */
        $collections = $response->json('collections');

        return $collections;
    }

    /**
     * @return TestResponse<JsonResponse>
     */
    private function request(DungeonRoute $dungeonRoute): TestResponse
    {
        return $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('ajax.collections.fordungeonroute', ['dungeon_route' => $dungeonRoute->public_key]));
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
     * @param array<string, mixed> $attributes
     */
    private function createRoute(User $author, MappingVersion $mappingVersion, ?Season $season = null, array $attributes = []): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create(array_merge([
            'author_id'          => $author->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $season?->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ], $attributes));
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }

    /**
     * @return array<int, DungeonRoute>
     */
    private function createRoutes(User $author, int $count): array
    {
        $dungeonRoutes = [];
        for ($i = 0; $i < $count; $i++) {
            $dungeonRoutes[] = $this->createRoute($author, $this->retailMappingVersion());
        }

        return $dungeonRoutes;
    }

    /**
     * @param array<int, DungeonRoute> $dungeonRoutes In collection order.
     */
    private function createCollection(DungeonRouteCollectionFactory $factory, User $owner, array $dungeonRoutes = []): DungeonRouteCollection
    {
        /** @var DungeonRouteCollection $dungeonRouteCollection */
        $dungeonRouteCollection = $factory->create(['user_id' => $owner->id]);

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

    private function gameVersionWithoutSeasons(): GameVersion
    {
        return GameVersion::query()->where('has_seasons', false)->where('active', true)->orderBy('id')->firstOrFail();
    }

    private function createRetailSeason(): Season
    {
        return $this->createSeason(['expansion_id' => $this->retail()->expansion_id], [$this->retailMappingVersion()->dungeon_id]);
    }

    private function retailMappingVersion(): MappingVersion
    {
        return $this->retailMappingVersions()->firstOrFail();
    }

    /**
     * Retail mapping versions of distinct challenge mode dungeons, newest first.
     *
     * @return Collection<int, MappingVersion>
     */
    private function retailMappingVersions(): Collection
    {
        return MappingVersion::query()
            ->where('game_version_id', $this->retail()->id)
            ->whereHas('dungeon', static fn(Builder $query) => $query->whereNotNull('challenge_mode_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('dungeon_id')
            ->values();
    }
}
