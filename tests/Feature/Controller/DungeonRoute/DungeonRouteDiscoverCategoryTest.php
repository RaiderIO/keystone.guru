<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Features\DungeonRouteListRework;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('Discover')]
final class DungeonRouteDiscoverCategoryTest extends PublicTestCase
{
    #[Test]
    public function discoverDungeonPopular_givenReworkFlagActive_returnsRankedLeaderboard(): void
    {
        // Arrange
        Feature::define(DungeonRouteListRework::class, true);
        [$gameVersion, $dungeon, $routes] = $this->createQualifyingRoutes(1);

        try {
            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon.popular', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert - the reworked ranked leaderboard is rendered instead of the legacy panel
            $response->assertOk();
            $response->assertSee('card_dungeonroute leaderboard_row', false);
            $response->assertSee('leaderboard_rank', false);
            $response->assertDontSee('id="category_route_list"', false);
        } finally {
            $routes->each(fn(DungeonRoute $route) => $route->delete());
        }
    }

    #[Test]
    public function discoverDungeonPopular_givenReworkFlagInactive_returnsLegacyPanel(): void
    {
        // Arrange
        Feature::define(DungeonRouteListRework::class, false);
        [$gameVersion, $dungeon, $routes] = $this->createQualifyingRoutes(1);

        try {
            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon.popular', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert - the unchanged legacy panel (infinite-scroll container) is rendered
            $response->assertOk();
            $response->assertSee('id="category_route_list"', false);
            $response->assertDontSee('card_dungeonroute leaderboard_row', false);
        } finally {
            $routes->each(fn(DungeonRoute $route) => $route->delete());
        }
    }

    #[Test]
    public function discoverDungeonNew_givenReworkFlagActive_returnsRankedLeaderboard(): void
    {
        // Arrange
        Feature::define(DungeonRouteListRework::class, true);
        [$gameVersion, $dungeon, $routes] = $this->createQualifyingRoutes(1);

        try {
            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon.new', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee('card_dungeonroute leaderboard_row', false);
            $response->assertDontSee('id="category_route_list"', false);
        } finally {
            $routes->each(fn(DungeonRoute $route) => $route->delete());
        }
    }

    #[Test]
    public function discoverDungeonNew_givenReworkFlagInactive_returnsLegacyPanel(): void
    {
        // Arrange
        Feature::define(DungeonRouteListRework::class, false);
        [$gameVersion, $dungeon, $routes] = $this->createQualifyingRoutes(1);

        try {
            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon.new', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee('id="category_route_list"', false);
            $response->assertDontSee('card_dungeonroute leaderboard_row', false);
        } finally {
            $routes->each(fn(DungeonRoute $route) => $route->delete());
        }
    }

    #[Test]
    public function discoverDungeonPopular_givenReworkFlagActiveAndSecondPage_continuesRankNumbering(): void
    {
        // Arrange - shrink the page size so a second page exists with only a few routes
        Feature::define(DungeonRouteListRework::class, true);
        config(['keystoneguru.discover.limits.category' => 2]);
        [$gameVersion, $dungeon, $routes] = $this->createQualifyingRoutes(3);

        try {
            // Act - the first page (rank 1..2, with a "next" link to page 2)
            $firstPage = $this->get(route('dungeonroutes.discoverdungeon.popular', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Act - the second page (rank continues at 3, with a "previous" link back to page 1)
            $secondPage = $this->get(route('dungeonroutes.discoverdungeon.popular', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]) . '?page=2');

            // Assert - page one starts at rank 1 and offers a next page
            $firstPage->assertOk();
            $firstPage->assertSee('leaderboard_rank text-secondary text-end">1</div>', false);
            $firstPage->assertSee('rel="next"', false);
            $firstPage->assertDontSee('rel="prev"', false);

            // Assert - page two continues the ranking at perPage + 1 (= 3) and offers a previous page
            $secondPage->assertOk();
            $secondPage->assertSee('leaderboard_rank text-secondary text-end">3</div>', false);
            $secondPage->assertSee('rel="prev"', false);
        } finally {
            $routes->each(fn(DungeonRoute $route) => $route->delete());
        }
    }

    #[Test]
    public function discoverDungeonThisWeek_givenAnyState_redirectsToPopular(): void
    {
        // Arrange
        [$gameVersion, $dungeon] = $this->activeDungeon();

        // Act
        $response = $this->get(route('dungeonroutes.discoverdungeon.thisweek', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert - the retired affix page permanently redirects to popular
        $response->assertStatus(301);
        $response->assertRedirect(route('dungeonroutes.discoverdungeon.popular', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));
    }

    #[Test]
    public function discoverDungeonNextWeek_givenAnyState_redirectsToPopular(): void
    {
        // Arrange
        [$gameVersion, $dungeon] = $this->activeDungeon();

        // Act
        $response = $this->get(route('dungeonroutes.discoverdungeon.nextweek', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('dungeonroutes.discoverdungeon.popular', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));
    }

    /**
     * Resolves an active dungeon (with a current mapping version and floors) plus its game version.
     * @return array{0: GameVersion, 1: Dungeon}
     */
    private function activeDungeon(): array
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->where('active', true)
            ->whereNotNull('challenge_mode_id')
            ->with('floors')
            ->get()
            ->first(fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersion() !== null && $dungeon->floors->isNotEmpty());

        $this->assertNotNull($dungeon, 'Expected an active dungeon with a mapping version in the seeded database');

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $gameVersion    = GameVersion::findOrFail($mappingVersion->game_version_id);

        return [$gameVersion, $dungeon];
    }

    /**
     * Creates published, non-expired routes on an active dungeon that satisfy the discover popular/new
     * filters (enemy forces at the required threshold), so they surface in the leaderboard.
     * @return array{0: GameVersion, 1: Dungeon, 2: Collection<int, DungeonRoute>}
     */
    private function createQualifyingRoutes(int $count): array
    {
        [$gameVersion, $dungeon] = $this->activeDungeon();

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $activeSeason   = $dungeon->getActiveSeason(app(SeasonServiceInterface::class));

        $routes = DungeonRoute::factory()->count($count)->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $activeSeason?->id,
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'teeming'            => false,
            'enemy_forces'       => $mappingVersion->enemy_forces_required,
            'expires_at'         => null,
            'published_at'       => Carbon::now(),
        ]);

        return [$gameVersion, $dungeon, $routes];
    }
}
