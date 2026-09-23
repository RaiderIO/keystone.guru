<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
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
#[Group('Discover')]
final class FeaturedCreatorsTest extends PublicTestCase
{
    #[Test]
    public function getFeaturedCreators_givenACreatorAboveTheThreshold_includesThem(): void
    {
        // Arrange - a second creator, because the rail hides itself with only one to show
        $dungeon = $this->featuredDungeon();
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes(), $dungeon);
        $other   = User::factory()->create();
        $routes->push(...$this->createPublishedRoutesFor($other, $this->minPublishedRoutes(), $dungeon));

        try {
            // Act - a generous limit, because the featured rail is ranked by popularity for the
            // dungeon and a brand new creator sits below the established ones
            $featured = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators($dungeon, PHP_INT_MAX);

            // Assert
            $this->assertTrue(
                $featured->pluck('id')->contains($creator->id),
                'A creator at the threshold must be eligible to be featured',
            );
        } finally {
            $this->deleteAll($routes);
            $creator->delete();
            $other->delete();
        }
    }

    /**
     * The featured rail and the directory must agree on who counts as a creator - they share
     * buildListedCreatorsQuery() precisely so the opt-out cannot be honoured on one and ignored on
     * the other.
     */
    #[Test]
    public function getFeaturedCreators_givenACreatorWhoOptedOut_excludesThem(): void
    {
        // Arrange
        $dungeon = $this->featuredDungeon();
        $creator = User::factory()->create(['hide_from_creator_directory' => true]);
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes(), $dungeon);

        try {
            // Act
            $featured = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators($dungeon, PHP_INT_MAX);

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

    /**
     * Arranging three eligible creators is what gives this test teeth: asserting "at most 2" against
     * whatever the database happens to hold passes on an empty result, and keeps passing with the
     * limit() removed entirely.
     */
    #[Test]
    public function getFeaturedCreators_givenALimit_returnsExactlyThatMany(): void
    {
        // Arrange
        $dungeon             = $this->featuredDungeon();
        [$creators, $routes] = $this->createCreatorsFor($dungeon, 3);

        try {
            // Act
            $featured = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators($dungeon, 2);

            // Assert
            $this->assertCount(2, $featured, 'The limit must be respected');
        } finally {
            $this->deleteAll($routes);
            $creators->each(fn(User $creator) => $creator->delete());
        }
    }

    #[Test]
    public function getFeaturedCreators_givenFewerCreatorsThanTheMinimum_returnsNothing(): void
    {
        // Arrange - a minimum above anything the database can hold, so the arranged creators fall short
        config(['keystoneguru.creators.featured_min_count' => PHP_INT_MAX]);
        $dungeon             = $this->featuredDungeon();
        [$creators, $routes] = $this->createCreatorsFor($dungeon, 2);

        try {
            // Act
            $featured = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators($dungeon, PHP_INT_MAX);

            // Assert
            $this->assertTrue($featured->isEmpty(), 'The rail must hide itself below the minimum number of creators');
        } finally {
            $this->deleteAll($routes);
            $creators->each(fn(User $creator) => $creator->delete());
        }
    }

    #[Test]
    public function discoverDungeon_givenCreatorProfilesActive_rendersTheFeaturedRail(): void
    {
        // Arrange - two creators for the dungeon guarantee the rail has enough entries to render
        Feature::define(CreatorProfiles::class, true);

        $dungeon             = $this->featuredDungeon();
        [$creators, $routes] = $this->createCreatorsFor($dungeon, 2);

        try {
            // The rail is truncated to featured_count and ranked by popularity, so the creators
            // arranged above aren't guaranteed a spot on a seeded database. Assert against whoever
            // the service actually features instead of assuming it is them.
            $featuredCreator = app(CreatorDirectoryServiceInterface::class)->getFeaturedCreators($dungeon)->first();
            $this->assertNotNull($featuredCreator, 'Expected at least one listed creator to feature');

            // Act
            $response = $this->get($this->dungeonRouteListUrl($dungeon));

            // Assert - the rail, its label naming the dungeon and linking into the directory, and the featured creator
            $response->assertOk();
            $response->assertSee('discover_creator_rail', false);
            $response->assertSee(e(__('view_creator.featured.title_dungeon', ['dungeon' => __($dungeon->name)])), false);
            $response->assertSee(route('creators.index'), false);
            $response->assertSee($featuredCreator->name);
        } finally {
            $this->deleteAll($routes);
            $creators->each(fn(User $creator) => $creator->delete());
        }
    }

    #[Test]
    public function discoverDungeon_givenCreatorProfilesInactive_doesNotRenderTheFeaturedRail(): void
    {
        // Arrange
        Feature::define(CreatorProfiles::class, false);

        $dungeon = $this->featuredDungeon();

        // Act
        $response = $this->get($this->dungeonRouteListUrl($dungeon));

        // Assert
        $response->assertOk();
        $response->assertDontSee('discover_creator_rail', false);
        $response->assertDontSee(e(__('view_creator.featured.title_dungeon', ['dungeon' => __($dungeon->name)])), false);
    }

    /**
     * An active dungeon with a mapping version: the per-dungeon route page - the page "Browse
     * routes" actually lands on, and the one the featured rail opens - renders for it.
     */
    private function featuredDungeon(): Dungeon
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->where('active', true)
            ->whereNotNull('challenge_mode_id')
            ->with('floors')
            ->get()
            ->first(fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersion() !== null && $dungeon->floors->isNotEmpty());

        $this->assertNotNull($dungeon, 'Expected an active dungeon with a mapping version in the seeded database');

        return $dungeon;
    }

    private function dungeonRouteListUrl(Dungeon $dungeon): string
    {
        return route('dungeonroutes.discoverdungeon', [
            'gameVersion' => GameVersion::findOrFail($dungeon->getCurrentMappingVersion()->game_version_id),
            'dungeon'     => $dungeon,
        ]);
    }

    /** @return array{EloquentCollection<int, User>, EloquentCollection<int, DungeonRoute>} */
    private function createCreatorsFor(Dungeon $dungeon, int $count): array
    {
        $creators = new EloquentCollection();
        $routes   = new EloquentCollection();

        for ($i = 0; $i < $count; $i++) {
            $creator = User::factory()->create();
            $creators->push($creator);
            $routes->push(...$this->createPublishedRoutesFor($creator, $this->minPublishedRoutes(), $dungeon));
        }

        return [$creators, $routes];
    }

    private function minPublishedRoutes(): int
    {
        return (int)config('keystoneguru.creators.min_published_routes');
    }

    /** @return EloquentCollection<int, DungeonRoute> */
    private function createPublishedRoutesFor(User $creator, int $count, Dungeon $dungeon): EloquentCollection
    {
        $routes = new EloquentCollection();
        // The rail counts the dungeon's routes of the season it is in now, whatever the factory would pick
        $season = app(SeasonServiceInterface::class)->getCurrentSeasonForDungeon($dungeon);

        for ($i = 0; $i < $count; $i++) {
            $routes->push(DungeonRoute::factory()->create([
                'author_id'          => $creator->id,
                'dungeon_id'         => $dungeon->id,
                'season_id'          => $season?->id,
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
