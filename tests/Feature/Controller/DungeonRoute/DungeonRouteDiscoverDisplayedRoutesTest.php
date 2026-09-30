<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Database\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Discover')]
final class DungeonRouteDiscoverDisplayedRoutesTest extends PublicTestCase
{
    #[Test]
    public function discoverDungeon_givenLeaderboardShowingRoute_reportsRouteAsDisplayed(): void
    {
        // Arrange
        $dungeonRoute = null;

        try {
            [$gameVersion, $dungeon, $dungeonRoute] = $this->createQualifyingRoute();
            $displayedRouteIds                      = $this->captureDisplayedRouteIds();

            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert
            $response->assertOk();
            $this->assertContains($dungeonRoute->id, $displayedRouteIds->all());
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[DataProvider('discoverDungeon_leaderboardPageProvider')]
    public function discoverDungeon_givenLeaderboardPage_reportsWeeklyRouteOnlyOnTheFirstPage(int $page, bool $expectWeeklyRoute): void
    {
        // Arrange
        $weeklyDungeonRoute = null;

        try {
            [$gameVersion, $dungeon, $weeklyDungeonRoute] = $this->createQualifyingRoute();
            // Unpublished, so it can only be reported through the weekly slot and never through the leaderboard
            $weeklyDungeonRoute->update(['published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED]]);

            $dungeonRouteRepository = $this->getMockBuilderPublic(DungeonRouteRepository::class)
                ->setConstructorArgs([app()->make(SeasonServiceInterface::class)])
                ->onlyMethods(['getWeeklyRoutes'])
                ->getMock();
            $dungeonRouteRepository->method('getWeeklyRoutes')->willReturn(collect([
                $dungeon->key => collect([new WeeklyRoute('weekly', $weeklyDungeonRoute)]),
            ]));
            app()->instance(DungeonRouteRepositoryInterface::class, $dungeonRouteRepository);

            $displayedRouteIds = $this->captureDisplayedRouteIds();

            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'page'        => $page,
            ]));

            // Assert
            $response->assertOk();
            $this->assertSame($expectWeeklyRoute, in_array($weeklyDungeonRoute->id, $displayedRouteIds->all(), true));
        } finally {
            $weeklyDungeonRoute?->delete();
        }
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function discoverDungeon_leaderboardPageProvider(): array
    {
        return [
            'first page'  => [1, true],
            'second page' => [2, false],
        ];
    }

    /**
     * @param array<int, string> $parameterKeys
     * @param array<int, string> $viewKeys      The view data holding the routes the page renders.
     */
    #[Test]
    #[DataProvider('page_givenRouteListingPage_provider')]
    public function page_givenRouteListingPage_reportsTheRoutesItRenders(string $routeName, array $parameterKeys, array $viewKeys): void
    {
        // Arrange
        $gameVersion = GameVersion::getDefaultGameVersion();
        $season      = app(SeasonServiceInterface::class)->getCurrentSeason($gameVersion->expansion);
        $dungeon     = $season->dungeons()->active()->firstOrFail();
        $parameters  = collect([
            'gameVersion' => $gameVersion,
            'expansion'   => $gameVersion->expansion,
            'season'      => $season->index,
            'dungeon'     => $dungeon,
        ])->only($parameterKeys)->all();

        $dungeonRoute = null;

        try {
            $dungeonRoute = $this->createQualifyingRouteOn($dungeon, $gameVersion, $season);
            Cache::store('tmp_file')->flush();

            $displayedRouteIds = $this->captureDisplayedRouteIds(1);

            // Act
            $response = $this->get(route($routeName, $parameters));

            // Assert
            $response->assertOk();
            $renderedRouteIds = collect($viewKeys)
                ->flatMap(fn(string $viewKey) => $this->collectDungeonRouteIds($response->viewData($viewKey)));
            $this->assertContains($dungeonRoute->id, $renderedRouteIds->all(), 'The page must render a route, or comparing it against the reported routes proves nothing.');
            $this->assertEqualsCanonicalizing($renderedRouteIds->unique()->values()->all(), $displayedRouteIds->unique()->values()->all());
        } finally {
            $dungeonRoute?->delete();
            Cache::store('tmp_file')->flush();
        }
    }

    /**
     * @return array<string, array{string, array<int, string>, array<int, string>}>
     */
    public static function page_givenRouteListingPage_provider(): array
    {
        return [
            'home'             => ['home', [], ['popularDungeonRoutesByDungeon']],
            'affixes'          => ['misc.affixes', [], ['dungeonroutes']],
            'popular'          => ['dungeonroutes.popular', ['gameVersion'], ['dungeonroutes']],
            'new'              => ['dungeonroutes.new', ['gameVersion'], ['dungeonroutes']],
            'season'           => ['dungeonroutes.season', ['gameVersion', 'season'], ['dungeonroutes']],
            'season popular'   => ['dungeonroutes.season.popular', ['gameVersion', 'season'], ['dungeonroutes']],
            'season new'       => ['dungeonroutes.season.new', ['gameVersion', 'season'], ['dungeonroutes']],
            'expansion'        => ['dungeonroutes.expansion', ['expansion'], ['dungeonroutes']],
            'expansion season' => ['dungeonroutes.expansion.season', ['expansion', 'season'], ['dungeonroutes']],
        ];
    }

    /**
     * Swaps in a ThumbnailService mock that records the id of every route reported as displayed.
     *
     * @return Collection<int, int>
     */
    private function captureDisplayedRouteIds(?int $expectedCalls = null): Collection
    {
        $displayedRouteIds = collect();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($expectedCalls === null ? $this->atLeastOnce() : $this->exactly($expectedCalls))
            ->method('dungeonRoutesDisplayed')
            ->willReturnCallback(static function (Collection $dungeonRoutes) use ($displayedRouteIds): bool {
                $dungeonRoutes->each(static fn(DungeonRoute $dungeonRoute) => $displayedRouteIds->push($dungeonRoute->id));

                return false;
            });
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        return $displayedRouteIds;
    }

    /**
     * @return Collection<int, int>
     */
    private function collectDungeonRouteIds(mixed $value): Collection
    {
        return match (true) {
            $value instanceof DungeonRoute                 => collect([$value->id]),
            $value instanceof LengthAwarePaginator         => $this->collectDungeonRouteIds($value->items()),
            $value instanceof Collection, is_array($value) => collect($value)->flatMap(fn(mixed $item) => $this->collectDungeonRouteIds($item)),
            default                                        => collect(),
        };
    }

    /**
     * A published, non-expired route on $dungeon's mapping version for $gameVersion that satisfies the discover filters,
     * tagged with $season's affix group of this week so the affix overview lists it too.
     */
    private function createQualifyingRouteOn(Dungeon $dungeon, GameVersion $gameVersion, Season $season): DungeonRoute
    {
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        $this->assertNotNull($mappingVersion, 'Expected the current season dungeon to be mapped for the default game version');

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $dungeon->getActiveSeason(app(SeasonServiceInterface::class))?->id,
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'teeming'            => false,
            'enemy_forces'       => $mappingVersion->enemy_forces_required,
            'expires_at'         => null,
            'published_at'       => Carbon::now(),
        ]);

        $currentAffixGroup = app(SeasonAffixGroupServiceInterface::class)->getCurrentAffixGroup($season);
        $this->assertNotNull($currentAffixGroup, 'Expected the current season to have an affix group this week');
        DungeonRouteAffixGroup::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'affix_group_id'   => $currentAffixGroup->id,
        ]);

        return $dungeonRoute;
    }

    /**
     * A published, non-expired route on an active dungeon that satisfies the discover filters.
     *
     * @return array{0: GameVersion, 1: Dungeon, 2: DungeonRoute}
     */
    private function createQualifyingRoute(): array
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

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $dungeon->getActiveSeason(app(SeasonServiceInterface::class))?->id,
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'teeming'            => false,
            'enemy_forces'       => $mappingVersion->enemy_forces_required,
            'expires_at'         => null,
            'published_at'       => Carbon::now(),
        ]);

        return [GameVersion::findOrFail($mappingVersion->game_version_id), $dungeon, $dungeonRoute];
    }
}
