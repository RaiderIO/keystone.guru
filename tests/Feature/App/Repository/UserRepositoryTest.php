<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Database\UserRepository;
use App\Service\Creator\Enums\CreatorDirectorySort;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('UserRepository')]
final class UserRepositoryTest extends PublicTestCase
{
    private UserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserRepository();
    }

    #[Test]
    public function buildListedCreatorsQuery_givenACreatorAboveTheThreshold_listsThem(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        try {
            // Act
            $result = $this->repository->buildListedCreatorsQuery()->get();

            // Assert
            $this->assertTrue($result->pluck('id')->contains($creator->id));
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function buildListedCreatorsQuery_givenACreatorBelowTheThreshold_doesNotListThem(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes() - 1);

        try {
            // Act
            $result = $this->repository->buildListedCreatorsQuery()->get();

            // Assert
            $this->assertFalse($result->pluck('id')->contains($creator->id));
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function buildListedCreatorsQuery_givenACreatorWhoOptedOut_doesNotListThem(): void
    {
        // Arrange
        $creator = User::factory()->create(['hide_from_creator_directory' => true]);
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        try {
            // Act
            $result = $this->repository->buildListedCreatorsQuery()->get();

            // Assert
            $this->assertFalse($result->pluck('id')->contains($creator->id));
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function buildListedCreatorsQuery_givenACreator_eagerLoadsIconfile(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        try {
            // Act
            $result = $this->repository->buildListedCreatorsQuery()->get();

            // Assert
            $listedCreator = $result->firstWhere('id', $creator->id);
            $this->assertNotNull($listedCreator);
            $this->assertTrue($listedCreator->relationLoaded('iconfile'));
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    /**
     * The published route count is exposed on the returned models so the threshold and the count
     * rendered on each card come from the same aggregate. If a future edit drops the extra select,
     * this is the only thing that would catch it - the controller test only asserts creator ids.
     */
    #[Test]
    public function buildListedCreatorsQuery_givenACreator_exposesPublishedRouteCount(): void
    {
        // Arrange
        $creator     = User::factory()->create();
        $extraRoutes = 2;
        $routes      = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes() + $extraRoutes);

        try {
            // Act
            $result = $this->repository->buildListedCreatorsQuery()->get();

            // Assert
            $listedCreator = $result->firstWhere('id', $creator->id);
            $this->assertNotNull($listedCreator);
            $this->assertEquals($this->minPublishedRoutes() + $extraRoutes, $listedCreator->published_route_count);
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    /**
     * Ordering is by published_route_count descending, with users.id as a stable tiebreak - without
     * it, pagination could repeat or skip a creator whenever two creators tie on route count.
     */
    #[Test]
    public function buildListedCreatorsQuery_givenCreatorsWithDifferentCounts_ordersByPublishedRouteCountDescending(): void
    {
        // Arrange
        $fewerRoutesCreator = User::factory()->create();
        $moreRoutesCreator  = User::factory()->create();
        $fewerRoutes        = $this->createPublishedRoutesFor($fewerRoutesCreator, $this->minPublishedRoutes());
        $moreRoutes         = $this->createPublishedRoutesFor($moreRoutesCreator, $this->minPublishedRoutes() + 1);

        try {
            // Act
            $result = $this->repository->buildListedCreatorsQuery()->get();

            // Assert
            $ids            = $result->pluck('id');
            $moreRoutesIdx  = $ids->search($moreRoutesCreator->id);
            $fewerRoutesIdx = $ids->search($fewerRoutesCreator->id);
            $this->assertLessThan($fewerRoutesIdx, $moreRoutesIdx, 'A creator with more published routes must be listed first');
        } finally {
            $this->deleteAll($fewerRoutes);
            $this->deleteAll($moreRoutes);
            $fewerRoutesCreator->delete();
            $moreRoutesCreator->delete();
        }
    }

    /**
     * Two creators tied on published_route_count must still resolve to a deterministic order, or
     * pagination could repeat or skip one of them between pages.
     */
    #[Test]
    public function buildListedCreatorsQuery_givenCreatorsWithTiedCounts_tiebreaksByUserId(): void
    {
        // Arrange
        $creatorA = User::factory()->create();
        $creatorB = User::factory()->create();
        $routesA  = $this->createPublishedRoutesFor($creatorA, $this->minPublishedRoutes());
        $routesB  = $this->createPublishedRoutesFor($creatorB, $this->minPublishedRoutes());

        [$lowerIdCreator, $higherIdCreator] = $creatorA->id < $creatorB->id
            ? [$creatorA, $creatorB]
            : [$creatorB, $creatorA];

        try {
            // Act
            $result = $this->repository->buildListedCreatorsQuery()->get();

            // Assert
            $ids           = $result->pluck('id');
            $lowerIdIndex  = $ids->search($lowerIdCreator->id);
            $higherIdIndex = $ids->search($higherIdCreator->id);
            $this->assertLessThan($higherIdIndex, $lowerIdIndex, 'On a tie, the lower user id must be listed first');
        } finally {
            $this->deleteAll($routesA);
            $this->deleteAll($routesB);
            $creatorA->delete();
            $creatorB->delete();
        }
    }

    #[Test]
    public function buildListedCreatorsQuery_givenASeason_exposesSeasonAndTotalFigures(): void
    {
        // Arrange
        [$seasonId, $otherSeasonId] = $this->twoSeasonIds();
        $creator                    = User::factory()->create();
        $routes                     = $this->createRoutesFor($creator, [
            ['season_id' => $seasonId, 'views' => 100, 'popularity' => 7],
            ['season_id' => $seasonId, 'views' => 50, 'popularity' => 3],
            ['season_id' => $otherSeasonId, 'views' => 1000, 'popularity' => 90],
        ]);

        try {
            // Act
            $listedCreator = $this->repository->buildListedCreatorsQuery(null, $seasonId)->get()->firstWhere('id', $creator->id);

            // Assert
            $this->assertNotNull($listedCreator);
            $this->assertEquals(3, $listedCreator->published_route_count);
            $this->assertEquals(1150, $listedCreator->total_views);
            $this->assertEquals(2, $listedCreator->season_route_count);
            $this->assertEquals(150, $listedCreator->season_views);
            $this->assertEquals(10, $listedCreator->season_popularity);
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    /**
     * Without a season the season figures must be zero - comparing season_id with a null season
     * would otherwise count the creator's routes that have no season at all.
     */
    #[Test]
    public function buildListedCreatorsQuery_givenNoSeason_returnsZeroSeasonFigures(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $routes  = $this->createRoutesFor($creator, array_fill(0, $this->minPublishedRoutes(), ['season_id' => null, 'views' => 10]));

        try {
            // Act
            $listedCreator = $this->repository->buildListedCreatorsQuery()->get()->firstWhere('id', $creator->id);

            // Assert
            $this->assertNotNull($listedCreator);
            $this->assertEquals(0, $listedCreator->season_route_count);
            $this->assertEquals(0, $listedCreator->season_views);
            $this->assertEquals(10 * $this->minPublishedRoutes(), $listedCreator->total_views);
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function buildListedCreatorsQuery_givenRoutesThatAreNotWorldPublished_leavesThemOutOfEveryFigure(): void
    {
        // Arrange
        [$seasonId] = $this->twoSeasonIds();
        $creator    = User::factory()->create();
        $routes     = $this->createRoutesFor($creator, array_fill(0, $this->minPublishedRoutes(), ['season_id' => $seasonId, 'views' => 1]));
        $routes->push(...$this->createRoutesFor($creator, [
            ['season_id' => $seasonId, 'views' => 500, 'rating' => 1, 'rating_count' => 50, 'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK]],
            ['season_id' => $seasonId, 'views' => 500, 'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED]],
            ['season_id' => $seasonId, 'views' => 500, 'published_state_id' => PublishedState::ALL[PublishedState::TEAM]],
        ]));

        try {
            // Act
            $listedCreator = $this->repository->buildListedCreatorsQuery(null, $seasonId)->get()->firstWhere('id', $creator->id);

            // Assert
            $this->assertNotNull($listedCreator);
            $this->assertEquals($this->minPublishedRoutes(), $listedCreator->published_route_count);
            $this->assertEquals($this->minPublishedRoutes(), $listedCreator->season_route_count);
            $this->assertEquals($this->minPublishedRoutes(), $listedCreator->season_views);
            $this->assertEquals($this->minPublishedRoutes(), $listedCreator->total_views);
            $this->assertEquals(0, $listedCreator->rating_count);
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function buildListedCreatorsQuery_givenActiveThisSeasonSort_listsCreatorsWithSeasonRoutesFirst(): void
    {
        // Arrange - the season creator has fewer routes, so only the season sort can put them first
        [$seasonId, $otherSeasonId] = $this->twoSeasonIds();
        $seasonCreator              = User::factory()->create();
        $pastCreator                = User::factory()->create();
        $seasonRoutes               = $this->createRoutesFor($seasonCreator, array_fill(0, $this->minPublishedRoutes(), ['season_id' => $seasonId]));
        $pastRoutes                 = $this->createRoutesFor($pastCreator, array_fill(0, $this->minPublishedRoutes() + 1, ['season_id' => $otherSeasonId, 'popularity' => 100]));

        try {
            // Act
            $seasonSortIds = $this->repository->buildListedCreatorsQuery(null, $seasonId, CreatorDirectorySort::ActiveThisSeason)->pluck('users.id');
            $routesSortIds = $this->repository->buildListedCreatorsQuery(null, $seasonId, CreatorDirectorySort::MostRoutes)->pluck('users.id');

            // Assert
            $this->assertLessThan($seasonSortIds->search($pastCreator->id), $seasonSortIds->search($seasonCreator->id));
            $this->assertLessThan($routesSortIds->search($seasonCreator->id), $routesSortIds->search($pastCreator->id));
        } finally {
            $this->deleteAll($seasonRoutes);
            $this->deleteAll($pastRoutes);
            $seasonCreator->delete();
            $pastCreator->delete();
        }
    }

    #[Test]
    public function buildListedCreatorsQuery_givenActiveThisSeasonSort_ordersBySeasonPopularity(): void
    {
        // Arrange - the less popular creator has more routes, so a count ordering would flip them
        [$seasonId]       = $this->twoSeasonIds();
        $popularCreator   = User::factory()->create();
        $unpopularCreator = User::factory()->create();
        $popularRoutes    = $this->createRoutesFor($popularCreator, array_fill(0, $this->minPublishedRoutes(), ['season_id' => $seasonId, 'popularity' => 50]));
        $unpopularRoutes  = $this->createRoutesFor($unpopularCreator, array_fill(0, $this->minPublishedRoutes() + 1, ['season_id' => $seasonId, 'popularity' => 1]));

        try {
            // Act
            $ids = $this->repository->buildListedCreatorsQuery(null, $seasonId, CreatorDirectorySort::ActiveThisSeason)->pluck('users.id');

            // Assert
            $this->assertLessThan($ids->search($unpopularCreator->id), $ids->search($popularCreator->id));
        } finally {
            $this->deleteAll($popularRoutes);
            $this->deleteAll($unpopularRoutes);
            $popularCreator->delete();
            $unpopularCreator->delete();
        }
    }

    #[Test]
    public function buildFeaturedCreatorsForDungeonQuery_givenCreatorsWithRoutesForTheDungeon_ranksThemByPopularityThere(): void
    {
        // Arrange - the less popular creator has more routes for the dungeon and far more popularity elsewhere
        [$seasonId]                   = $this->twoSeasonIds();
        [$dungeonId, $otherDungeonId] = $this->twoDungeonIds();
        $popularCreator               = User::factory()->create();
        $unpopularCreator             = User::factory()->create();
        $popularRoutes                = $this->createRoutesFor($popularCreator, array_fill(0, $this->minPublishedRoutes(), ['dungeon_id' => $dungeonId, 'season_id' => $seasonId, 'popularity' => 50]));
        $unpopularRoutes              = $this->createRoutesFor($unpopularCreator, [
            ...array_fill(0, $this->minPublishedRoutes() + 1, ['dungeon_id' => $dungeonId, 'season_id' => $seasonId, 'popularity' => 1]),
            ['dungeon_id' => $otherDungeonId, 'season_id' => $seasonId, 'popularity' => 1000],
        ]);

        try {
            // Act
            $featured = $this->repository->buildFeaturedCreatorsForDungeonQuery($dungeonId, $seasonId)->get();

            // Assert
            $ids = $featured->pluck('id');
            $this->assertLessThan($ids->search($unpopularCreator->id), $ids->search($popularCreator->id));
            $this->assertEquals($this->minPublishedRoutes() + 1, $featured->firstWhere('id', $unpopularCreator->id)->dungeon_route_count);
        } finally {
            $this->deleteAll($popularRoutes);
            $this->deleteAll($unpopularRoutes);
            $popularCreator->delete();
            $unpopularCreator->delete();
        }
    }

    #[Test]
    public function buildFeaturedCreatorsForDungeonQuery_givenOnlyRoutesForOtherDungeonsOrSeasons_excludesTheCreator(): void
    {
        // Arrange
        [$seasonId, $otherSeasonId]   = $this->twoSeasonIds();
        [$dungeonId, $otherDungeonId] = $this->twoDungeonIds();
        $creator                      = User::factory()->create();
        $routes                       = $this->createRoutesFor($creator, [
            ...array_fill(0, $this->minPublishedRoutes(), ['dungeon_id' => $otherDungeonId, 'season_id' => $seasonId]),
            ['dungeon_id' => $dungeonId, 'season_id' => $otherSeasonId],
        ]);

        try {
            // Act
            $withSeason    = $this->repository->buildFeaturedCreatorsForDungeonQuery($dungeonId, $seasonId)->pluck('users.id');
            $withoutSeason = $this->repository->buildFeaturedCreatorsForDungeonQuery($dungeonId, null)->pluck('users.id');

            // Assert - without a season, any of the dungeon's routes count
            $this->assertNotContains($creator->id, $withSeason);
            $this->assertContains($creator->id, $withoutSeason);
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function buildFeaturedCreatorsForDungeonQuery_givenACreatorWhoIsNotListed_excludesThem(): void
    {
        // Arrange
        [$dungeonId]    = $this->twoDungeonIds();
        $optedOut       = User::factory()->create(['hide_from_creator_directory' => true]);
        $belowThreshold = User::factory()->create();
        $optedOutRoutes = $this->createRoutesFor($optedOut, array_fill(0, $this->minPublishedRoutes(), ['dungeon_id' => $dungeonId]));
        $belowRoutes    = $this->createRoutesFor($belowThreshold, array_fill(0, $this->minPublishedRoutes() - 1, ['dungeon_id' => $dungeonId]));

        try {
            // Act
            $ids = $this->repository->buildFeaturedCreatorsForDungeonQuery($dungeonId, null)->pluck('users.id');

            // Assert
            $this->assertNotContains($optedOut->id, $ids);
            $this->assertNotContains($belowThreshold->id, $ids);
        } finally {
            $this->deleteAll($optedOutRoutes);
            $this->deleteAll($belowRoutes);
            $optedOut->delete();
            $belowThreshold->delete();
        }
    }

    #[Test]
    public function getCreatorStatsAttributes_givenRatedRoutes_returnsTheRatingWeightsAndLatestPublishDate(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $routes  = $this->createRoutesFor($creator, [
            ['rating' => 4, 'rating_count' => 3, 'published_at' => '2026-01-01 00:00:00'],
            ['rating' => 5, 'rating_count' => 1, 'published_at' => '2026-03-01 00:00:00'],
        ]);

        try {
            // Act
            $attributes = $this->repository->getCreatorStatsAttributes($creator->id, null);

            // Assert
            $this->assertEquals(17, $attributes['rating_weighted_sum']);
            $this->assertEquals(4, $attributes['rating_count']);
            $this->assertEquals('2026-03-01 00:00:00', $attributes['last_published_at']);
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function getCreatorStatsAttributes_givenNoWorldPublishedRoutes_returnsAnEmptyArray(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $routes  = $this->createRoutesFor($creator, [['published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED]]]);

        try {
            // Act
            $attributes = $this->repository->getCreatorStatsAttributes($creator->id, null);

            // Assert
            $this->assertSame([], $attributes);
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    private function minPublishedRoutes(): int
    {
        return (int)config('keystoneguru.creators.min_published_routes');
    }

    /** @return EloquentCollection<int, DungeonRoute> */
    private function createPublishedRoutesFor(User $creator, int $count): EloquentCollection
    {
        $routes = new EloquentCollection();

        for ($i = 0; $i < $count; $i++) {
            $routes->push(DungeonRoute::factory()->create([
                'author_id'          => $creator->id,
                'expires_at'         => null,
                'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            ]));
        }

        return $routes;
    }

    /**
     * @param list<array<string, mixed>> $attributesPerRoute One route per entry, world-published unless overridden.
     *
     * @return EloquentCollection<int, DungeonRoute>
     */
    private function createRoutesFor(User $creator, array $attributesPerRoute): EloquentCollection
    {
        $routes = new EloquentCollection();

        foreach ($attributesPerRoute as $attributes) {
            $routes->push(DungeonRoute::factory()->create(array_merge([
                'author_id'          => $creator->id,
                'expires_at'         => null,
                'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            ], $attributes)));
        }

        return $routes;
    }

    /** @return array{int, int} */
    private function twoSeasonIds(): array
    {
        $seasonIds = Season::query()->orderBy('id')->limit(2)->pluck('id');
        $this->assertCount(2, $seasonIds, 'Expected at least two seeded seasons');

        return [$seasonIds[0], $seasonIds[1]];
    }

    /** @return array{int, int} */
    private function twoDungeonIds(): array
    {
        $dungeonIds = Dungeon::query()->whereNotNull('challenge_mode_id')->orderBy('id')->limit(2)->pluck('id');
        $this->assertCount(2, $dungeonIds, 'Expected at least two seeded dungeons');

        return [$dungeonIds[0], $dungeonIds[1]];
    }

    /** @param EloquentCollection<int, DungeonRoute> $routes */
    private function deleteAll(EloquentCollection $routes): void
    {
        foreach ($routes as $route) {
            $route->delete();
        }
    }
}
