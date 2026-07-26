<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\Creator\CreatorDirectoryServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class FeaturedCreatorsTest extends PublicTestCase
{
    #[Test]
    public function getFeaturedCreators_givenACreatorAboveTheThreshold_includesThem(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        try {
            // Act - a generous limit, because the featured row is ranked by published route count
            // and a brand new creator at the threshold sits below the established ones
            $featured = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators(PHP_INT_MAX);

            // Assert
            $this->assertTrue(
                $featured->pluck('id')->contains($creator->id),
                'A creator at the threshold must be eligible to be featured',
            );
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    /**
     * The featured row and the directory must agree on who counts as a creator - they share
     * buildListedCreatorsQuery() precisely so the opt-out cannot be honoured on one and ignored on
     * the other.
     */
    #[Test]
    public function getFeaturedCreators_givenACreatorWhoOptedOut_excludesThem(): void
    {
        // Arrange
        $creator = User::factory()->create(['hide_from_creator_directory' => true]);
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        try {
            // Act
            $featured = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators(PHP_INT_MAX);

            // Assert
            $this->assertFalse(
                $featured->pluck('id')->contains($creator->id),
                'A creator who opted out must not be featured on the discover landing either',
            );
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function getFeaturedCreators_givenALimit_returnsAtMostThatMany(): void
    {
        // Act
        $featured = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators(2);

        // Assert
        $this->assertLessThanOrEqual(2, $featured->count(), 'The limit must be respected');
    }

    #[Test]
    public function discover_givenFeatureInactive_doesNotRenderTheFeaturedRow(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->deactivate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->followingRedirects()->get($this->discoverUrl());

            // Assert
            $response->assertOk();
            $response->assertDontSee(__('view_creator.featured.title'));
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function discover_givenFeatureActiveAndAListedCreator_rendersTheFeaturedRow(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->followingRedirects()->get($this->discoverUrl());

            // Assert
            $response->assertOk();
            $response->assertSee(__('view_creator.featured.title'));
            $response->assertSee(__('view_creator.featured.see_all'));
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    /**
     * A URL that actually renders dungeonroute.discover.discover.
     *
     * Deliberately not route('dungeonroutes'): that redirects by game version and then by the
     * viewer's dungeon context, which in a seeded environment lands on a per-dungeon page that
     * renders a different view entirely.
     */
    private function discoverUrl(): string
    {
        $season = app(SeasonServiceInterface::class)->getCurrentSeason();

        return route('dungeonroutes.expansion.season', [
            'expansion' => $season->expansion,
            'season'    => $season->index,
        ]);
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
