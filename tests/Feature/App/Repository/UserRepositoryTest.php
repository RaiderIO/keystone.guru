<?php

namespace Tests\Feature\App\Repository;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use App\Repositories\Database\UserRepository;
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

    /** @param EloquentCollection<int, DungeonRoute> $routes */
    private function deleteAll(EloquentCollection $routes): void
    {
        foreach ($routes as $route) {
            $route->delete();
        }
    }
}
