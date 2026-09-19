<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use Database\Factories\DungeonRoute\DungeonRouteCollectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

/**
 * The ways into a new collection besides a blank form: from one route ("New collection with this route…"), from a
 * tag, and by duplicating a collection - and the collection cap that closes all of them.
 */
#[Group('Controller')]
#[Group('DungeonRouteCollection')]
final class DungeonRouteCollectionControllerDuplicateTest extends PublicTestCase
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

                Tag::query()->where('context_id', $user->id)->where('context_class', User::class)->delete();
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
    public function duplicate_givenTheOwnerAndTheSameSeason_copiesEverythingIntoAnOnlyMeCollection(): void
    {
        // Arrange
        $owner    = $this->createUser();
        $season   = $this->createRetailSeason();
        $category = DungeonRouteCollectionCategory::query()->firstOrFail();
        $alpha    = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        $bravo    = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        $source   = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season), $owner, [$bravo, $alpha], [
            'name'                                 => 'ZzTest set',
            'description'                          => 'ZzTest description',
            'dungeon_route_collection_category_id' => $category->id,
            'published_state_id'                   => PublishedState::ALL[PublishedState::WORLD],
        ]);

        // Act
        $response = $this->actingAs($owner)->post($this->duplicateUrl($source), ['season_id' => $season->id]);

        // Assert
        $duplicate = $this->latestCollectionOf($owner);
        $this->assertNotSame($source->id, $duplicate->id);
        $response->assertRedirect(route('collections.edit', ['dungeonRouteCollection' => $duplicate]));
        $response->assertSessionMissing('warning');
        $this->assertSame('ZzTest set', $duplicate->name);
        $this->assertSame('ZzTest description', $duplicate->description);
        $this->assertSame($category->id, $duplicate->dungeon_route_collection_category_id);
        $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $duplicate->published_state_id);
        $this->assertNull($duplicate->team_id);
        $this->assertSame($source->game_version_id, $duplicate->game_version_id);
        $this->assertSame($season->id, $duplicate->season_id);
        $this->assertSame([$bravo->id, $alpha->id], $this->memberIds($duplicate));
        $this->assertSame([$bravo->id, $alpha->id], $this->memberIds($source), 'The source keeps its routes');
    }

    #[Test]
    public function duplicate_givenAnotherSeason_leavesOutTheRoutesOfTheOldSeasonWithANotice(): void
    {
        // Arrange
        $owner     = $this->createUser();
        $oldSeason = $this->createRetailSeason();
        $newSeason = $this->createRetailSeason();
        $old       = $this->createRoute($owner, $this->retailMappingVersion(), $oldSeason);
        $current   = $this->createRoute($owner, $this->retailMappingVersion(), $newSeason);
        $source    = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner, [$old, $current]);

        // Act
        $response = $this->actingAs($owner)->post($this->duplicateUrl($source), ['season_id' => $newSeason->id]);

        // Assert
        $duplicate = $this->latestCollectionOf($owner);
        $response->assertRedirect(route('collections.edit', ['dungeonRouteCollection' => $duplicate]));
        $response->assertSessionHas('warning', trans_choice('controller.dungeonroutecollection.flash.collection_duplicated_left_out', 1, ['count' => 1]));
        $this->assertSame($newSeason->id, $duplicate->season_id);
        $this->assertSame([$current->id], $this->memberIds($duplicate));
    }

    #[Test]
    public function duplicate_givenNoSeason_makesAFreeFormCopyThatKeepsEveryRouteOfTheGameVersion(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $season = $this->createRetailSeason();
        $alpha  = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        $source = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season), $owner, [$alpha]);

        // Act
        $response = $this->actingAs($owner)->post($this->duplicateUrl($source), ['season_id' => '']);

        // Assert
        $duplicate = $this->latestCollectionOf($owner);
        $response->assertRedirect(route('collections.edit', ['dungeonRouteCollection' => $duplicate]));
        $this->assertNull($duplicate->season_id);
        $this->assertSame([$alpha->id], $this->memberIds($duplicate));
    }

    #[Test]
    public function duplicate_givenASeasonOnAGameVersionWithoutSeasons_failsValidation(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $source = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->gameVersionWithoutSeasons()), $owner);
        $season = $this->createRetailSeason();

        // Act
        $response = $this->actingAs($owner)->post($this->duplicateUrl($source), ['season_id' => $season->id]);

        // Assert
        $response->assertSessionHasErrors(['season_id']);
        $this->assertSame(1, DungeonRouteCollection::query()->where('user_id', $owner->id)->count());
    }

    #[Test]
    public function duplicate_givenASeasonOfAnotherExpansion_failsValidation(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $source = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);
        $season = $this->createSeason(['expansion_id' => $this->otherExpansionId()]);

        // Act
        $response = $this->actingAs($owner)->post($this->duplicateUrl($source), ['season_id' => $season->id]);

        // Assert
        $response->assertSessionHasErrors(['season_id']);
        $this->assertSame(1, DungeonRouteCollection::query()->where('user_id', $owner->id)->count());
    }

    #[Test]
    public function duplicate_givenSomeoneElsesCollection_returnsForbidden(): void
    {
        // Arrange
        $owner     = $this->createUser();
        $otherUser = $this->createUser();
        $source    = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);

        // Act
        $response = $this->actingAs($otherUser)->post($this->duplicateUrl($source), ['season_id' => '']);

        // Assert
        $response->assertForbidden();
        $this->assertSame(0, DungeonRouteCollection::query()->where('user_id', $otherUser->id)->count());
    }

    #[Test]
    public function duplicate_givenAnAdminWhoDoesNotOwnTheCollection_returnsForbidden(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $admin  = User::findOrFail(1);
        $source = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        Feature::for($admin)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($admin)->post($this->duplicateUrl($source), ['season_id' => '']);

            // Assert
            $response->assertForbidden();
            $this->assertSame(1, DungeonRouteCollection::query()->where('user_id', $owner->id)->count());
        } finally {
            Feature::for($admin)->forget(CreatorProfiles::class);
        }
    }

    #[Test]
    public function duplicate_givenAGuest_redirectsToLogin(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $source = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);

        // Act
        $response = $this->post($this->duplicateUrl($source), ['season_id' => '']);

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertSame(1, DungeonRouteCollection::query()->where('user_id', $owner->id)->count());
    }

    #[Test]
    public function duplicate_givenTheCollectionCap_rejectsTheCopy(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $source = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);
        $this->createCollections($owner, DungeonRouteCollection::MAX_COLLECTIONS - 1);

        // Act
        $response = $this->actingAs($owner)->post($this->duplicateUrl($source), ['season_id' => '']);

        // Assert
        $response->assertRedirect(route('collections.edit', ['dungeonRouteCollection' => $source]));
        $response->assertSessionHas('warning');
        $this->assertSame(DungeonRouteCollection::MAX_COLLECTIONS, DungeonRouteCollection::query()->where('user_id', $owner->id)->count());
    }

    #[Test]
    public function edit_givenTheCollectionCap_disablesDuplicateWithTheReason(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $source = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);
        $this->createCollections($owner, DungeonRouteCollection::MAX_COLLECTIONS - 1);

        // Act
        $response = $this->actingAs($owner)->get(route('collections.edit', ['dungeonRouteCollection' => $source]));

        // Assert
        $response->assertOk();
        $this->assertFalse($response->viewData('mayCreateCollection'));
        $response->assertSee('id="duplicate_collection_max"', false);
        $response->assertSeeText(__('view_collection.index.max_collections', ['max' => DungeonRouteCollection::MAX_COLLECTIONS]));
    }

    #[Test]
    public function edit_givenASeasonSet_countsTheRoutesADuplicateKeepsPerSeason(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $season = $this->createRetailSeason();
        $alpha  = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        $source = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season), $owner, [$alpha]);

        // Act
        $response = $this->actingAs($owner)->get(route('collections.edit', ['dungeonRouteCollection' => $source]));

        // Assert
        $response->assertOk();
        $this->assertTrue($response->viewData('mayDuplicate'));
        $counts = $response->viewData('duplicateMatchingCounts');
        $this->assertSame(1, $counts[$season->id]);
        $this->assertSame(1, $counts['']);
        $this->assertContains($season->id, $response->viewData('duplicateSeasons')->pluck('id')->all(), 'The source\'s own season is offered even when inactive');
    }

    #[Test]
    public function index_givenTheCollectionCap_disablesNewCollectionWithTheReason(): void
    {
        // Arrange
        $owner = $this->createUser();
        $this->createCollections($owner, DungeonRouteCollection::MAX_COLLECTIONS);

        // Act
        $response = $this->actingAs($owner)->get(route('collections.index'));

        // Assert
        $response->assertOk();
        $this->assertFalse($response->viewData('mayCreateCollection'));
        $response->assertSee('id="collections_max_collections"', false);
    }

    #[Test]
    public function create_givenTheCollectionCap_disablesTheSubmitWithTheReason(): void
    {
        // Arrange
        $owner = $this->createUser();
        $this->createCollections($owner, DungeonRouteCollection::MAX_COLLECTIONS);

        // Act
        $response = $this->actingAs($owner)->get(route('collections.new'));

        // Assert
        $response->assertOk();
        $this->assertFalse($response->viewData('mayCreateCollection'));
        $response->assertSee('id="collection_max_collections"', false);
    }

    #[Test]
    public function create_givenOneOfTheUsersRoutes_startsFromItsGameVersionAndSeasonWithTheRouteSelected(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $season       = $this->createRetailSeason();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion(), $season);

        // Act
        $response = $this->actingAs($owner)->get(route('collections.new', ['dungeon_route' => $dungeonRoute->public_key]));

        // Assert
        $response->assertOk();
        $this->assertSame($this->retail()->id, $response->viewData('selectedGameVersion')->id);
        $this->assertSame($season->id, $response->viewData('selectedSeason')?->id);
        $this->assertSame([$dungeonRoute->id], $response->viewData('selectedDungeonRouteIds'));
    }

    #[Test]
    public function create_givenARouteOfAGameVersionWithoutSeasons_startsFreeForm(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->mappingVersionWithoutSeasons());

        // Act
        $response = $this->actingAs($owner)->get(route('collections.new', ['dungeon_route' => $dungeonRoute->public_key]));

        // Assert
        $response->assertOk();
        $this->assertSame($this->mappingVersionWithoutSeasons()->game_version_id, $response->viewData('selectedGameVersion')->id);
        $this->assertNull($response->viewData('selectedSeason'));
        $this->assertSame([$dungeonRoute->id], $response->viewData('selectedDungeonRouteIds'));
    }

    #[Test]
    public function create_givenSomeoneElsesRoute_failsValidation(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $otherUser    = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());

        // Act
        $response = $this->actingAs($otherUser)->get(route('collections.new', ['dungeon_route' => $dungeonRoute->public_key]));

        // Assert
        $response->assertSessionHasErrors(['dungeon_route']);
    }

    #[Test]
    public function create_givenATag_preselectsOnlyTheTaggedRoutesOfTheChosenSeason(): void
    {
        // Arrange
        $owner            = $this->createUser();
        $season           = $this->createRetailSeason();
        $otherSeason      = $this->createRetailSeason();
        $tagged           = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        $wrongSeason      = $this->createRoute($owner, $this->retailMappingVersion(), $otherSeason);
        $wrongGameVersion = $this->createRoute($owner, $this->mappingVersionWithoutSeasons());
        $untagged         = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        foreach ([$tagged, $wrongSeason, $wrongGameVersion] as $dungeonRoute) {
            $this->tag($owner, $dungeonRoute, 'ZzTest tag');
        }
        $this->tag($owner, $untagged, 'ZzTest other tag');

        // Act
        $response = $this->actingAs($owner)->get(route('collections.new', [
            'game_version_id' => $this->retail()->id,
            'season_id'       => $season->id,
            'tag'             => 'ZzTest tag',
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame([$tagged->id], $response->viewData('selectedDungeonRouteIds'));
        $this->assertSame(2, $response->viewData('tagDungeonRoutesLeftOut'));
        $this->assertSame('ZzTest tag', $response->viewData('selectedTagName'));
        $this->assertEqualsCanonicalizing(['ZzTest tag', 'ZzTest other tag'], $response->viewData('tagNames')->all());
        $response->assertSee('id="collection_tag_routes_left_out"', false);
    }

    #[Test]
    public function create_givenATagAndAFreeFormCollection_preselectsTheTaggedRoutesOfEverySeason(): void
    {
        // Arrange
        $owner       = $this->createUser();
        $season      = $this->createRetailSeason();
        $otherSeason = $this->createRetailSeason();
        $alpha       = $this->createRoute($owner, $this->retailMappingVersion(), $season, 'ZzTest Alpha');
        $bravo       = $this->createRoute($owner, $this->retailMappingVersion(), $otherSeason, 'ZzTest Bravo');
        $this->tag($owner, $alpha, 'ZzTest tag');
        $this->tag($owner, $bravo, 'ZzTest tag');

        // Act
        $response = $this->actingAs($owner)->get(route('collections.new', [
            'game_version_id' => $this->retail()->id,
            'season_id'       => '',
            'tag'             => 'ZzTest tag',
        ]));

        // Assert
        $response->assertOk();
        $this->assertNull($response->viewData('selectedSeason'));
        $this->assertSame([$alpha->id, $bravo->id], $response->viewData('selectedDungeonRouteIds'));
        $this->assertSame(0, $response->viewData('tagDungeonRoutesLeftOut'));
    }

    #[Test]
    public function create_givenSomeoneElsesTag_failsValidation(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $otherUser    = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());
        $this->tag($owner, $dungeonRoute, 'ZzTest private tag');

        // Act
        $response = $this->actingAs($otherUser)->get(route('collections.new', ['tag' => 'ZzTest private tag']));

        // Assert
        $response->assertSessionHasErrors(['tag']);
    }

    #[Test]
    public function create_givenASeasonOnAGameVersionWithoutSeasons_failsValidation(): void
    {
        // Arrange
        $owner  = $this->createUser();
        $season = $this->createRetailSeason();

        // Act
        $response = $this->actingAs($owner)->get(route('collections.new', [
            'game_version_id' => $this->gameVersionWithoutSeasons()->id,
            'season_id'       => $season->id,
        ]));

        // Assert
        $response->assertSessionHasErrors(['season_id']);
    }

    private function duplicateUrl(DungeonRouteCollection $dungeonRouteCollection): string
    {
        return route('collections.duplicate', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }

    private function latestCollectionOf(User $user): DungeonRouteCollection
    {
        return DungeonRouteCollection::query()->where('user_id', $user->id)->orderByDesc('id')->firstOrFail();
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);
        Feature::for($user)->activate(CreatorProfiles::class);
        $this->createdUsers[] = $user;

        return $user;
    }

    private function createRoute(User $author, MappingVersion $mappingVersion, ?Season $season = null, ?string $title = null): DungeonRoute
    {
        $attributes = [
            'author_id'          => $author->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $season?->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ];

        if ($title !== null) {
            $attributes['title'] = $title;
        }

        $dungeonRoute                 = DungeonRoute::factory()->create($attributes);
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }

    private function tag(User $user, DungeonRoute $dungeonRoute, string $name): void
    {
        Tag::create([
            'context_id'      => $user->id,
            'context_class'   => User::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
            'model_id'        => $dungeonRoute->id,
            'model_class'     => DungeonRoute::class,
            'name'            => $name,
            'color'           => null,
        ]);
    }

    /**
     * @param array<int, DungeonRoute> $dungeonRoutes In collection order.
     * @param array<string, mixed>     $attributes
     */
    private function createCollection(DungeonRouteCollectionFactory $factory, User $owner, array $dungeonRoutes = [], array $attributes = []): DungeonRouteCollection
    {
        /** @var DungeonRouteCollection $dungeonRouteCollection */
        $dungeonRouteCollection = $factory->create(array_merge(['user_id' => $owner->id], $attributes));

        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        return $dungeonRouteCollection;
    }

    private function createCollections(User $owner, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), $owner);
        }
    }

    /**
     * @return array<int, int> Route ids in collection order.
     */
    private function memberIds(DungeonRouteCollection $dungeonRouteCollection): array
    {
        return DungeonRouteCollectionRoute::query()
            ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
            ->orderBy('order')
            ->pluck('dungeon_route_id')
            ->all();
    }

    private function retail(): GameVersion
    {
        return GameVersion::query()->findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]);
    }

    private function gameVersionWithoutSeasons(): GameVersion
    {
        return GameVersion::query()->where('has_seasons', false)->where('active', true)->orderBy('id')->firstOrFail();
    }

    private function mappingVersionWithoutSeasons(): MappingVersion
    {
        return MappingVersion::query()
            ->where('game_version_id', $this->gameVersionWithoutSeasons()->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function otherExpansionId(): int
    {
        return GameVersion::query()
            ->where('expansion_id', '!=', $this->retail()->expansion_id)
            ->whereNotNull('expansion_id')
            ->firstOrFail()
            ->expansion_id;
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
