<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategoryType;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class DungeonRouteCollectionControllerBrowseTest extends PublicTestCase
{
    #[Test]
    public function browse_givenFeatureInactive_returnsNotFound(): void
    {
        // Arrange
        $visitor = $this->createUser();
        Feature::for($visitor)->deactivate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse'));

            // Assert
            $response->assertNotFound();
        } finally {
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $visitor->delete();
        }
    }

    #[Test]
    public function browse_givenAWorldPublishedCollection_listsItWithItsAuthor(): void
    {
        // Arrange
        $visitor      = $this->createUser();
        $author       = $this->createUser();
        $dungeonRoute = $this->createRouteFor($author, PublishedState::WORLD);
        Feature::for($visitor)->activate(CreatorProfiles::class);

        $name                   = $this->uniqueName();
        $dungeonRouteCollection = $this->createCollectionFor($author, [$dungeonRoute], ['name' => $name]);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse'));

            // Assert
            $response->assertOk();
            $this->assertContains($dungeonRouteCollection->id, $this->listedCollectionIds($response));
            $response->assertSee($name);
            $response->assertSee(__('view_common.collection.card.by_author', ['author' => $author->name]));
        } finally {
            $this->deleteCollection($dungeonRouteCollection);
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $author->delete();
            $visitor->delete();
        }
    }

    #[Test]
    public function browse_givenCollectionsThatAreNotWorldPublished_listsOnlyTheWorldPublishedOne(): void
    {
        // Arrange
        $visitor      = $this->createUser();
        $author       = $this->createUser();
        $dungeonRoute = $this->createRouteFor($author, PublishedState::WORLD);
        Feature::for($visitor)->activate(CreatorProfiles::class);

        $search                  = $this->uniqueName();
        $dungeonRouteCollections = collect([
            PublishedState::WORLD           => $this->createCollectionFor($author, [$dungeonRoute], ['name' => sprintf('%s world', $search)]),
            PublishedState::WORLD_WITH_LINK => $this->createCollectionFor($author, [$dungeonRoute], [
                'name'               => sprintf('%s link', $search),
                'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
            ]),
            PublishedState::TEAM => $this->createCollectionFor($author, [$dungeonRoute], [
                'name'               => sprintf('%s team', $search),
                'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
            ]),
            PublishedState::UNPUBLISHED => $this->createCollectionFor($author, [$dungeonRoute], [
                'name'               => sprintf('%s unpublished', $search),
                'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            ]),
        ]);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse', ['search' => $search]));

            // Assert
            $response->assertOk();
            $this->assertSame(
                [$dungeonRouteCollections->get(PublishedState::WORLD)->id],
                $this->listedCollectionIds($response),
            );
        } finally {
            $dungeonRouteCollections->each(fn(DungeonRouteCollection $dungeonRouteCollection) => $this->deleteCollection($dungeonRouteCollection));
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $author->delete();
            $visitor->delete();
        }
    }

    #[Test]
    public function browse_givenACollectionHoldingOnlyUnpublishedRoutes_doesNotListIt(): void
    {
        // Arrange
        $visitor          = $this->createUser();
        $author           = $this->createUser();
        $publishedRoute   = $this->createRouteFor($author, PublishedState::WORLD);
        $unpublishedRoute = $this->createRouteFor($author, PublishedState::UNPUBLISHED);
        Feature::for($visitor)->activate(CreatorProfiles::class);

        $search           = $this->uniqueName();
        $openCollection   = $this->createCollectionFor($author, [$publishedRoute], ['name' => sprintf('%s open', $search)]);
        $closedCollection = $this->createCollectionFor($author, [$unpublishedRoute], ['name' => sprintf('%s closed', $search)]);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse', ['search' => $search]));

            // Assert
            $response->assertOk();
            $this->assertSame([$openCollection->id], $this->listedCollectionIds($response));
        } finally {
            $this->deleteCollection($openCollection);
            $this->deleteCollection($closedCollection);
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $publishedRoute->delete();
            $unpublishedRoute->delete();
            $author->delete();
            $visitor->delete();
        }
    }

    #[Test]
    public function browse_givenAListedCollectionWithAnUnpublishedRoute_leavesThatRouteOffTheTile(): void
    {
        // Arrange
        $visitor          = $this->createUser();
        $author           = $this->createUser();
        $publishedRoute   = $this->createRouteFor($author, PublishedState::WORLD);
        $unpublishedRoute = $this->createRouteFor($author, PublishedState::UNPUBLISHED);
        Feature::for($visitor)->activate(CreatorProfiles::class);

        $search                 = $this->uniqueName();
        $dungeonRouteCollection = $this->createCollectionFor($author, [$publishedRoute, $unpublishedRoute], ['name' => $search]);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse', ['search' => $search]));

            // Assert
            $response->assertOk();
            /** @var Collection<int, Collection<int, DungeonRoute>> $tileDungeonRoutes */
            $tileDungeonRoutes = $response->viewData('tileDungeonRoutes');
            $this->assertSame(
                [$publishedRoute->id],
                $tileDungeonRoutes->get($dungeonRouteCollection->id)->pluck('id')->all(),
            );
        } finally {
            $this->deleteCollection($dungeonRouteCollection);
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $publishedRoute->delete();
            $unpublishedRoute->delete();
            $author->delete();
            $visitor->delete();
        }
    }

    #[Test]
    public function browse_givenASearch_listsOnlyCollectionsWhoseNameMatches(): void
    {
        // Arrange
        $visitor      = $this->createUser();
        $author       = $this->createUser();
        $dungeonRoute = $this->createRouteFor($author, PublishedState::WORLD);
        Feature::for($visitor)->activate(CreatorProfiles::class);

        $search          = $this->uniqueName();
        $matchingName    = $this->createCollectionFor($author, [$dungeonRoute], ['name' => sprintf('Week 3 %s picks', $search)]);
        $nonMatchingName = $this->createCollectionFor($author, [$dungeonRoute], ['name' => $this->uniqueName()]);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse', ['search' => $search]));

            // Assert
            $response->assertOk();
            $this->assertSame([$matchingName->id], $this->listedCollectionIds($response));
        } finally {
            $this->deleteCollection($matchingName);
            $this->deleteCollection($nonMatchingName);
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $author->delete();
            $visitor->delete();
        }
    }

    #[Test]
    public function browse_givenACategory_listsOnlyCollectionsOfThatCategory(): void
    {
        // Arrange
        $visitor      = $this->createUser();
        $author       = $this->createUser();
        $dungeonRoute = $this->createRouteFor($author, PublishedState::WORLD);
        Feature::for($visitor)->activate(CreatorProfiles::class);

        $search = $this->uniqueName();
        $expert = $this->createCollectionFor($author, [$dungeonRoute], [
            'name'                                 => sprintf('%s expert', $search),
            'dungeon_route_collection_category_id' => DungeonRouteCollectionCategoryType::Expert->id(),
        ]);
        $beginner = $this->createCollectionFor($author, [$dungeonRoute], [
            'name'                                 => sprintf('%s beginner', $search),
            'dungeon_route_collection_category_id' => DungeonRouteCollectionCategoryType::Beginner->id(),
        ]);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse', [
                'search'      => $search,
                'category_id' => DungeonRouteCollectionCategoryType::Expert->id(),
            ]));

            // Assert
            $response->assertOk();
            $this->assertSame([$expert->id], $this->listedCollectionIds($response));
        } finally {
            $this->deleteCollection($expert);
            $this->deleteCollection($beginner);
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $author->delete();
            $visitor->delete();
        }
    }

    #[Test]
    public function browse_givenADungeon_listsOnlyCollectionsHoldingAnOpenableRouteForIt(): void
    {
        // Arrange
        $visitor                                        = $this->createUser();
        $author                                         = $this->createUser();
        [$filteredMappingVersion, $otherMappingVersion] = $this->retailMappingVersionsOfDistinctDungeons();

        $filteredDungeonRoute            = $this->createRouteFor($author, PublishedState::WORLD, $filteredMappingVersion);
        $unpublishedFilteredDungeonRoute = $this->createRouteFor($author, PublishedState::UNPUBLISHED, $filteredMappingVersion);
        $otherDungeonRoute               = $this->createRouteFor($author, PublishedState::WORLD, $otherMappingVersion);
        Feature::for($visitor)->activate(CreatorProfiles::class);

        $search                  = $this->uniqueName();
        $withFilteredDungeon     = $this->createCollectionFor($author, [$filteredDungeonRoute], ['name' => sprintf('%s with', $search)]);
        $withOnlyOtherDungeon    = $this->createCollectionFor($author, [$otherDungeonRoute], ['name' => sprintf('%s other', $search)]);
        $withUnpublishedFiltered = $this->createCollectionFor(
            $author,
            [$unpublishedFilteredDungeonRoute, $otherDungeonRoute],
            ['name' => sprintf('%s unpublished', $search)],
        );

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse', [
                'search'  => $search,
                'dungeon' => $filteredMappingVersion->dungeon->slug,
            ]));

            // Assert
            $response->assertOk();
            $this->assertSame([$withFilteredDungeon->id], $this->listedCollectionIds($response));
        } finally {
            $this->deleteCollection($withFilteredDungeon);
            $this->deleteCollection($withOnlyOtherDungeon);
            $this->deleteCollection($withUnpublishedFiltered);
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $filteredDungeonRoute->delete();
            $unpublishedFilteredDungeonRoute->delete();
            $otherDungeonRoute->delete();
            $author->delete();
            $visitor->delete();
        }
    }

    #[Test]
    public function browse_givenACollectionOfAnotherGameVersion_doesNotListIt(): void
    {
        // Arrange
        $visitor      = $this->createUser();
        $author       = $this->createUser();
        $dungeonRoute = $this->createRouteFor($author, PublishedState::WORLD);
        Feature::for($visitor)->activate(CreatorProfiles::class);

        $search  = $this->uniqueName();
        $retail  = $this->createCollectionFor($author, [$dungeonRoute], ['name' => sprintf('%s retail', $search)]);
        $classic = $this->createCollectionFor($author, [$dungeonRoute], [
            'name'            => sprintf('%s classic', $search),
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
        ]);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse', ['search' => $search]));

            // Assert
            $response->assertOk();
            $this->assertSame([$retail->id], $this->listedCollectionIds($response));
        } finally {
            $this->deleteCollection($retail);
            $this->deleteCollection($classic);
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $author->delete();
            $visitor->delete();
        }
    }

    /**
     * @param array<string, mixed> $query
     */
    #[Test]
    #[DataProvider('browse_givenAnInvalidFilter_failsValidation_dataProvider')]
    public function browse_givenAnInvalidFilter_failsValidation(array $query, string $expectedErrorKey): void
    {
        // Arrange
        $visitor = $this->createUser();
        Feature::for($visitor)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('collection.browse', $query));

            // Assert
            $response->assertSessionHasErrors($expectedErrorKey);
        } finally {
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $visitor->delete();
        }
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function browse_givenAnInvalidFilter_failsValidation_dataProvider(): array
    {
        return [
            'unknown category' => [['category_id' => 99999], 'category_id'],
            'unknown dungeon'  => [['dungeon' => 'not-a-dungeon-slug'], 'dungeon'],
            'search too long'  => [['search' => str_repeat('a', DungeonRouteCollection::MAX_NAME_LENGTH + 1)], 'search'],
        ];
    }

    #[Test]
    public function header_givenFeatureActive_linksToTheCollectionsPage(): void
    {
        // Arrange
        $visitor = $this->createUser();
        Feature::for($visitor)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('home'));

            // Assert
            $response->assertOk();
            $response->assertSee(sprintf('href="%s"', route('collection.browse')), false);
        } finally {
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $visitor->delete();
        }
    }

    #[Test]
    public function header_givenFeatureInactive_doesNotLinkToTheCollectionsPage(): void
    {
        // Arrange
        $visitor = $this->createUser();
        Feature::for($visitor)->deactivate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($visitor)->get(route('home'));

            // Assert
            $response->assertOk();
            $response->assertSee(sprintf('href="%s"', route('dungeon.dungeonroute.search')), false);
            $response->assertDontSee(sprintf('href="%s"', route('collection.browse')), false);
        } finally {
            Feature::for($visitor)->forget(CreatorProfiles::class);
            $visitor->delete();
        }
    }

    private function createUser(): User
    {
        $user = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]]);
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    private function uniqueName(): string
    {
        return sprintf('ZzTestBrowse%s', Str::random(12));
    }

    private function createRouteFor(User $user, string $publishedState, ?MappingVersion $mappingVersion = null): DungeonRoute
    {
        $mappingVersion ??= $this->retailMappingVersionsOfDistinctDungeons()[0];

        return DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => null,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ]);
    }

    /**
     * @param array<int, DungeonRoute> $dungeonRoutes
     * @param array<string, mixed>     $attributes
     */
    private function createCollectionFor(User $user, array $dungeonRoutes, array $attributes): DungeonRouteCollection
    {
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(array_merge([
            'user_id'            => $user->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ], $attributes));

        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        return $dungeonRouteCollection;
    }

    private function deleteCollection(DungeonRouteCollection $dungeonRouteCollection): void
    {
        $dungeonRouteCollection->delete();
    }

    /**
     * @return array{0: MappingVersion, 1: MappingVersion}
     */
    private function retailMappingVersionsOfDistinctDungeons(): array
    {
        $mappingVersions = MappingVersion::query()
            ->with('dungeon')
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->whereHas('dungeon', static fn($query) => $query->whereNotNull('challenge_mode_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('dungeon_id')
            ->values();

        return [$mappingVersions->get(0), $mappingVersions->get(1)];
    }

    /**
     * @param  TestResponse<Response> $response
     * @return array<int, int>
     */
    private function listedCollectionIds(TestResponse $response): array
    {
        /** @var LengthAwarePaginator<int, DungeonRouteCollection> $dungeonRouteCollections */
        $dungeonRouteCollections = $response->viewData('dungeonRouteCollections');

        return collect($dungeonRouteCollections->items())->pluck('id')->all();
    }
}
