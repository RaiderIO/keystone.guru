<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Features\DungeonRouteListRework;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
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
        Feature::define(DungeonRouteListRework::class, true);
        $dungeonRoute = null;

        try {
            [$gameVersion, $dungeon, $dungeonRoute] = $this->createQualifyingRoute();

            $displayedRouteIds = collect();
            $thumbnailService  = $this->createMockPublic(ThumbnailServiceInterface::class);
            $thumbnailService->expects($this->once())
                ->method('dungeonRoutesDisplayed')
                ->willReturnCallback(static function (Collection $dungeonRoutes) use ($displayedRouteIds): bool {
                    $dungeonRoutes->filter()->each(static fn(DungeonRoute $dungeonRoute) => $displayedRouteIds->push($dungeonRoute->id));

                    return false;
                });
            app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

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

    /**
     * @param array<int, string> $parameterKeys
     */
    #[Test]
    #[DataProvider('page_givenRouteListingPage_provider')]
    public function page_givenRouteListingPage_reportsItsRoutesAsDisplayedOnce(string $routeName, array $parameterKeys): void
    {
        // Arrange
        Feature::define(DungeonRouteListRework::class, false);
        $gameVersion = GameVersion::getDefaultGameVersion();
        $season      = app(SeasonServiceInterface::class)->getCurrentSeason($gameVersion->expansion);
        $dungeon     = $season->dungeons()->active()->firstOrFail();
        $parameters  = collect([
            'gameVersion' => $gameVersion,
            'expansion'   => $gameVersion->expansion,
            'season'      => $season->index,
            'dungeon'     => $dungeon,
        ])->only($parameterKeys)->all();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->once())->method('dungeonRoutesDisplayed');
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        // Act
        $response = $this->get(route($routeName, $parameters));

        // Assert
        $response->assertOk();
    }

    /**
     * @return array<string, array{string, array<int, string>}>
     */
    public static function page_givenRouteListingPage_provider(): array
    {
        return [
            'home'                    => ['home', []],
            'affixes'                 => ['misc.affixes', []],
            'popular'                 => ['dungeonroutes.popular', ['gameVersion']],
            'new'                     => ['dungeonroutes.new', ['gameVersion']],
            'season'                  => ['dungeonroutes.season', ['gameVersion', 'season']],
            'season popular'          => ['dungeonroutes.season.popular', ['gameVersion', 'season']],
            'season new'              => ['dungeonroutes.season.new', ['gameVersion', 'season']],
            'expansion'               => ['dungeonroutes.expansion', ['expansion']],
            'expansion season'        => ['dungeonroutes.expansion.season', ['expansion', 'season']],
            'legacy dungeon overview' => ['dungeonroutes.discoverdungeon', ['gameVersion', 'dungeon']],
            'legacy dungeon popular'  => ['dungeonroutes.discoverdungeon.popular', ['gameVersion', 'dungeon']],
            'legacy dungeon new'      => ['dungeonroutes.discoverdungeon.new', ['gameVersion', 'dungeon']],
        ];
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
