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
    public function discoverDungeonPopular_givenReworkFlagActive_redirectsToBaseDungeonPage(): void
    {
        // Arrange - popular is folded into the base dungeon page when the rework is active
        Feature::define(DungeonRouteListRework::class, true);
        [$gameVersion, $dungeon] = $this->activeDungeon();

        // Act
        $response = $this->get(route('dungeonroutes.discoverdungeon.popular', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert - a permanent redirect to the base dungeon page
        $response->assertStatus(301);
        $response->assertRedirect(route('dungeonroutes.discoverdungeon', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));
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
    public function discoverDungeonNew_givenReworkFlagActive_redirectsToBaseDungeonPage(): void
    {
        // Arrange - the per-dungeon new category is retired into the base dungeon page when active
        Feature::define(DungeonRouteListRework::class, true);
        [$gameVersion, $dungeon] = $this->activeDungeon();

        // Act
        $response = $this->get(route('dungeonroutes.discoverdungeon.new', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('dungeonroutes.discoverdungeon', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));
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
    public function discoverDungeon_givenReworkFlagActive_returnsRankedLeaderboardWithHeroBand(): void
    {
        // Arrange - enough routes that some land in the leaderboard below the hero band
        Feature::define(DungeonRouteListRework::class, true);
        [$gameVersion, $dungeon, $routes] = $this->createQualifyingRoutes(5);

        try {
            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert - the reworked hero band + ranked leaderboard render instead of the legacy panels
            $response->assertOk();
            $response->assertSee('discover_hero_band', false);
            $response->assertSee('card_dungeonroute leaderboard_row', false);
            $response->assertSee('leaderboard_rank', false);
            $response->assertDontSee('id="category_route_list"', false);
        } finally {
            $routes->each(fn(DungeonRoute $route) => $route->delete());
        }
    }

    #[Test]
    public function discoverDungeon_givenReworkFlagInactive_returnsLegacyOverviewPanels(): void
    {
        // Arrange
        Feature::define(DungeonRouteListRework::class, false);
        [$gameVersion, $dungeon, $routes] = $this->createQualifyingRoutes(1);

        try {
            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert - the unchanged legacy multi-panel overview (no hero band, no leaderboard)
            $response->assertOk();
            $response->assertSee('id="category_route_list"', false);
            $response->assertDontSee('discover_hero_band', false);
            $response->assertDontSee('card_dungeonroute leaderboard_row', false);
        } finally {
            $routes->each(fn(DungeonRoute $route) => $route->delete());
        }
    }

    #[Test]
    public function discoverDungeon_givenReworkFlagActiveAndSecondPage_continuesRankAndHidesHeroBand(): void
    {
        // Arrange - a small page size so a second page exists; 6 routes spill onto page 2
        Feature::define(DungeonRouteListRework::class, true);
        config(['keystoneguru.discover.limits.leaderboard' => 4]);
        [$gameVersion, $dungeon, $routes] = $this->createQualifyingRoutes(6);

        try {
            // Act - page one, then page two (offset by perPage)
            $firstPage = $this->get(route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));
            $secondPage = $this->get(route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]) . '?page=2');

            // Assert - page one shows the hero band and offers a next page, but no previous
            $firstPage->assertOk();
            $firstPage->assertSee('discover_hero_band', false);
            $firstPage->assertSee('rel="next"', false);
            $firstPage->assertDontSee('rel="prev"', false);

            // Assert - page two continues the ranking at perPage + 1 (= 5), drops the hero band,
            // and offers a previous page
            $secondPage->assertOk();
            $secondPage->assertSee('leaderboard_rank text-secondary text-end">5</div>', false);
            $secondPage->assertSee('rel="prev"', false);
            $secondPage->assertDontSee('discover_hero_band', false);
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
