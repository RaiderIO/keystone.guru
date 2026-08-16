<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Features\DungeonRouteListRework;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\Creator\CreatorDirectoryServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Discover')]
final class FeaturedCreatorsTest extends PublicTestCase
{
    #[Test]
    public function getFeaturedCreators_givenACreatorAboveTheThreshold_includesThem(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        try {
            // Act - a generous limit, because the featured strip is ranked by published route count
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
     * The featured strip and the directory must agree on who counts as a creator - they share
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
                'A creator who opted out must not be featured on the route page either',
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
    public function discoverDungeon_givenBothFlagsActive_rendersTheFeaturedStrip(): void
    {
        // Arrange - a creator at the threshold guarantees the strip has at least one entry to render
        Feature::define(CreatorProfiles::class, true);
        Feature::define(DungeonRouteListRework::class, true);

        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        try {
            // The strip is truncated to featured_count and ranked by published route count, so the
            // creator arranged above isn't guaranteed a spot on a seeded database. Assert against
            // whoever the service actually features instead of assuming it is them.
            $featuredCreator = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators()->first();
            $this->assertNotNull($featuredCreator, 'Expected at least one listed creator to feature');

            // Act
            $response = $this->get($this->dungeonRouteListUrl());

            // Assert - the section, its heading link into the directory, and the featured creator
            $response->assertOk();
            $response->assertSee('discover_creator_strip', false);
            $response->assertSee(__('view_creator.featured.title'));
            $response->assertSee(route('creators.index'), false);
            $response->assertSee($featuredCreator->name);
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
        }
    }

    #[Test]
    public function discoverDungeon_givenCreatorProfilesInactive_doesNotRenderTheFeaturedStrip(): void
    {
        // Arrange
        Feature::define(CreatorProfiles::class, false);
        Feature::define(DungeonRouteListRework::class, true);

        // Act
        $response = $this->get($this->dungeonRouteListUrl());

        // Assert
        $response->assertOk();
        $response->assertDontSee('discover_creator_strip', false);
        $response->assertDontSee(__('view_creator.featured.title'));
    }

    /**
     * The strip is written for the reworked hero-band/leaderboard layout and is included from that
     * branch only - it must not leak into the legacy multi-panel overview it was never styled for.
     */
    #[Test]
    public function discoverDungeon_givenListReworkInactive_doesNotRenderTheFeaturedStrip(): void
    {
        // Arrange
        Feature::define(CreatorProfiles::class, true);
        Feature::define(DungeonRouteListRework::class, false);

        // Act
        $response = $this->get($this->dungeonRouteListUrl());

        // Assert
        $response->assertOk();
        $response->assertDontSee('discover_creator_strip', false);
        $response->assertDontSee(__('view_creator.featured.title'));
    }

    /**
     * The per-dungeon route page - the page "Browse routes" actually lands on, and the one the
     * featured strip closes.
     */
    private function dungeonRouteListUrl(): string
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->where('active', true)
            ->whereNotNull('challenge_mode_id')
            ->with('floors')
            ->get()
            ->first(fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersion() !== null && $dungeon->floors->isNotEmpty());

        $this->assertNotNull($dungeon, 'Expected an active dungeon with a mapping version in the seeded database');

        return route('dungeonroutes.discoverdungeon', [
            'gameVersion' => GameVersion::findOrFail($dungeon->getCurrentMappingVersion()->game_version_id),
            'dungeon'     => $dungeon,
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
