<?php

namespace Tests\Feature\App\Repository\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRouteRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Repositories\Interfaces\Tags\TagRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteCollection')]
final class DungeonRouteCollectionQueriesTest extends PublicTestCase
{
    /** @var array<int, DungeonRoute> */
    private array $routes = [];

    /** @var array<int, DungeonRouteCollection> */
    private array $collections = [];

    /** @var array<int, Tag> */
    private array $tags = [];

    private ?User $owner = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->beforeApplicationDestroyed(function (): void {
            foreach ($this->collections as $collection) {
                $collection->delete();
            }
            foreach ($this->tags as $tag) {
                $tag->delete();
            }
            foreach ($this->routes as $route) {
                $route->delete();
            }
            $this->owner?->delete();
        });
    }

    #[Test]
    public function hasRoutesWithoutExpiryByAuthor_givenOnlyASandboxRoute_returnsFalse(): void
    {
        // Arrange
        $this->route(['expires_at' => now()->addHour()]);

        // Act + Assert
        $this->assertFalse($this->routeRepository()->hasRoutesWithoutExpiryByAuthor($this->owner()));
    }

    #[Test]
    public function hasRoutesWithoutExpiryByAuthor_givenAPermanentRoute_returnsTrue(): void
    {
        // Arrange
        $this->route(['expires_at' => null]);

        // Act + Assert
        $this->assertTrue($this->routeRepository()->hasRoutesWithoutExpiryByAuthor($this->owner()));
    }

    #[Test]
    public function getWithoutExpiryByAuthorAndPublicKeys_givenMixedRoutes_returnsOnlyTheAuthorsPermanentOnesKeyedByPublicKey(): void
    {
        // Arrange
        $permanent = $this->route(['expires_at' => null]);
        $sandbox   = $this->route(['expires_at' => now()->addHour()]);
        $foreign   = $this->route(['expires_at' => null], User::factory()->create());

        // Act
        $result = $this->routeRepository()->getWithoutExpiryByAuthorAndPublicKeys(
            $this->owner(),
            [$permanent->public_key, $sandbox->public_key, $foreign->public_key],
        );

        // Assert
        $this->assertSame([$permanent->public_key], $result->keys()->all());
    }

    #[Test]
    public function getWithoutExpiryByAuthorAndIds_givenMixedRoutes_returnsOnlyTheAuthorsPermanentOnesOrderedById(): void
    {
        // Arrange
        $first   = $this->route(['expires_at' => null]);
        $second  = $this->route(['expires_at' => null]);
        $sandbox = $this->route(['expires_at' => now()->addHour()]);
        $foreign = $this->route(['expires_at' => null], User::factory()->create());

        // Act
        $result = $this->routeRepository()->getWithoutExpiryByAuthorAndIds(
            $this->owner(),
            [$second->id, $sandbox->id, $foreign->id, $first->id],
        );

        // Assert
        $this->assertSame([$first->id, $second->id], $result->pluck('id')->all());
    }

    #[Test]
    public function getMaxOrder_givenCollectionWithRoutes_returnsHighestOrderOfThatCollectionOnly(): void
    {
        // Arrange
        $collection = $this->collection([$this->route(), $this->route()]);
        $this->collection([$this->route(), $this->route(), $this->route(), $this->route(), $this->route()]);

        // Act + Assert
        $this->assertSame(1, $this->collectionRouteRepository()->getMaxOrder($collection));
    }

    #[Test]
    public function getMaxOrder_givenEmptyCollection_returnsZero(): void
    {
        // Act + Assert
        $this->assertSame(0, $this->collectionRouteRepository()->getMaxOrder($this->collection([])));
    }

    #[Test]
    public function deleteDungeonRoutes_givenRouteInTwoCollections_removesItFromTheGivenCollectionOnly(): void
    {
        // Arrange
        $route = $this->route();
        $kept  = $this->route();
        $one   = $this->collection([$route, $kept]);
        $two   = $this->collection([$route]);

        // Act
        $this->collectionRouteRepository()->deleteDungeonRoutes($one, collect([$route->id]));

        // Assert
        $this->assertSame([$kept->id], $this->collectionRouteRepository()->getDungeonRouteIds($one)->all());
        $this->assertSame([$route->id], $this->collectionRouteRepository()->getDungeonRouteIds($two)->all());
    }

    #[Test]
    public function updateOrder_givenRouteInTwoCollections_changesTheOrderInTheGivenCollectionOnly(): void
    {
        // Arrange
        $route = $this->route();
        $one   = $this->collection([$route]);
        $two   = $this->collection([$route]);

        // Act
        $this->collectionRouteRepository()->updateOrder($one, $route->id, 7);

        // Assert
        $this->assertSame(7, $this->collectionRouteRepository()->getMaxOrder($one));
        $this->assertSame(0, $this->collectionRouteRepository()->getMaxOrder($two));
    }

    #[Test]
    public function touchUpdatedAt_givenCollection_stampsUpdatedAt(): void
    {
        // Arrange
        $collection = $this->collection([]);
        DungeonRouteCollection::query()->whereKey($collection->id)->update(['updated_at' => now()->subDay()]);

        // Act
        app(DungeonRouteCollectionRepositoryInterface::class)->touchUpdatedAt($collection);

        // Assert
        $this->assertTrue($collection->fresh()->updated_at->isAfter(now()->subMinute()));
    }

    #[Test]
    public function getPersonalRouteIdsByTagName_givenTagsOfTwoNames_returnsIdsOfTheNamedTagOnly(): void
    {
        // Arrange
        $tagged   = $this->route();
        $untagged = $this->route();
        $this->tag($tagged, 'collection-test-tag');
        $this->tag($untagged, 'collection-test-other');

        // Act
        $result = app(TagRepositoryInterface::class)->getPersonalRouteIdsByTagName($this->owner(), 'collection-test-tag');

        // Assert
        $this->assertSame([$tagged->id], $result);
    }

    #[Test]
    public function findForExpansion_givenSeasonOfAnotherExpansion_returnsNull(): void
    {
        // Arrange
        $season = Season::query()->firstOrFail();
        $repo   = app(SeasonRepositoryInterface::class);

        // Act + Assert
        $this->assertSame($season->id, $repo->findForExpansion($season->expansion_id, $season->id)?->id);
        $this->assertNull($repo->findForExpansion($season->expansion_id + 1000, $season->id));
    }

    private function owner(): User
    {
        return $this->owner ??= User::factory()->create();
    }

    private function routeRepository(): DungeonRouteRepositoryInterface
    {
        return app(DungeonRouteRepositoryInterface::class);
    }

    private function collectionRouteRepository(): DungeonRouteCollectionRouteRepositoryInterface
    {
        return app(DungeonRouteCollectionRouteRepositoryInterface::class);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function route(array $attributes = [], ?User $author = null): DungeonRoute
    {
        $author ??= $this->owner();
        if ($author->id !== $this->owner()->id) {
            $this->beforeApplicationDestroyed(static fn() => $author->delete());
        }

        return $this->routes[] = DungeonRoute::factory()->create(array_merge(['author_id' => $author->id], $attributes));
    }

    /**
     * @param array<int, DungeonRoute> $routes
     */
    private function collection(array $routes): DungeonRouteCollection
    {
        $collection = DungeonRouteCollection::factory()
            ->freeForm(GameVersion::query()->findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]))
            ->create(['user_id' => $this->owner()->id]);
        $this->collections[] = $collection;

        foreach (array_values($routes) as $order => $route) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $collection->id,
                'dungeon_route_id'            => $route->id,
                'order'                       => $order,
            ]);
        }

        return $collection;
    }

    private function tag(DungeonRoute $route, string $name): void
    {
        $this->tags[] = Tag::create([
            'context_id'      => $this->owner()->id,
            'context_class'   => User::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
            'model_id'        => $route->id,
            'model_class'     => DungeonRoute::class,
            'name'            => $name,
        ]);
    }
}
