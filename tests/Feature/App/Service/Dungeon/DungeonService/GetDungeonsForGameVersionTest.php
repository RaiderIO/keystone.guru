<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\SeasonDungeon;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\DungeonService;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonService')]
#[Group('GetDungeonsForGameVersion')]
final class GetDungeonsForGameVersionTest extends PublicTestCase
{
    /**
     * Builds the service with everything but the season service stubbed out - the season service is
     * the only collaborator this method's behaviour depends on.
     */
    private function buildService(SeasonServiceInterface $seasonService): DungeonService
    {
        return new DungeonService(
            $this->createMockPublic(CookieServiceInterface::class),
            $seasonService,
            $this->createMockPublic(DungeonServiceLoggingInterface::class),
            $this->createMockPublic(GameVersionServiceInterface::class),
        );
    }

    /**
     * Scenario: a season has been seeded before its start date. Its dungeons are not playable yet, so the
     * selector must keep showing the current season's dungeons instead of swapping over to them (#3761).
     *
     * The seasons are deliberately loaded as a collection: preventLazyLoading only enforces on models
     * loaded as part of a multi-row result, which is how the site header used to be taken down (#3746).
     */
    #[Test]
    public function getDungeonsForGameVersion_givenAnUpcomingSeason_returnsTheCurrentSeasonsDungeons(): void
    {
        // Arrange
        $seasons = Season::query()->orderByDesc('id')->limit(2)->get();

        $this->assertCount(2, $seasons, 'Need at least two seeded seasons to load them as a collection');

        $nextSeason    = $seasons->first();
        $currentSeason = $seasons->last();

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($currentSeason);
        $seasonService->method('getNextSeason')->willReturn($nextSeason);

        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        // Act
        $dungeons = $this->buildService($seasonService)->getDungeonsForGameVersion($gameVersion);

        // Assert
        $this->assertEqualsCanonicalizing(
            $currentSeason->dungeons()->pluck('dungeons.id')->all(),
            $dungeons->pluck('id')->all(),
        );
    }

    /**
     * Scenario: no season has been seeded ahead of its start date, so there is nothing that could take
     * over the selector to begin with.
     */
    #[Test]
    public function getDungeonsForGameVersion_givenNoUpcomingSeason_returnsTheCurrentSeasonsDungeons(): void
    {
        // Arrange - loaded as a collection for the same reason as the test above
        $seasons       = Season::query()->orderByDesc('id')->limit(2)->get();
        $currentSeason = $seasons->last();

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($currentSeason);
        $seasonService->method('getNextSeason')->willReturn(null);

        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        // Act
        $dungeons = $this->buildService($seasonService)->getDungeonsForGameVersion($gameVersion);

        // Assert
        $this->assertEqualsCanonicalizing(
            $currentSeason->dungeons()->pluck('dungeons.id')->all(),
            $dungeons->pluck('id')->all(),
        );
    }

    /**
     * Scenario: an expansion without an active season - the game version's expansion is all we can show.
     */
    #[Test]
    public function getDungeonsForGameVersion_givenNoCurrentSeason_returnsTheExpansionsDungeons(): void
    {
        // Arrange
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn(null);
        $seasonService->method('getNextSeason')->willReturn(null);

        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        // Act
        $dungeons = $this->buildService($seasonService)->getDungeonsForGameVersion($gameVersion);

        // Assert
        $this->assertEqualsCanonicalizing(
            $gameVersion->expansion->dungeons->pluck('id')->all(),
            $dungeons->pluck('id')->all(),
        );
    }

    /**
     * Scenario: the real thing - a season for the current expansion is seeded weeks before it starts, the
     * way a new season is prepared for review and QA. The whole site's dungeon selector used to switch to
     * that season's dungeons the moment its row existed, dropping every current season dungeon (#3761).
     */
    #[Test]
    public function getDungeonsForGameVersion_givenAFutureSeasonSeededForTheCurrentExpansion_returnsOnlyTheCurrentSeasonsDungeons(): void
    {
        // Arrange - freezes "now" inside Season::SEASON_MIDNIGHT_S1's actual window (2026-03-02 to
        // 2026-08-17) so this test's assumption that S1 is current holds regardless of real wall-clock time
        $this->travelTo(Carbon::create(2026, 5, 28));

        $gameVersion   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $currentSeason = Season::findOrFail(Season::SEASON_MIDNIGHT_S1);
        $expansion     = Expansion::firstWhere('shortname', Expansion::EXPANSION_MIDNIGHT);
        // A dungeon that is not part of the current season, so the assert below can tell the two apart
        $futureSeasonDungeon = Dungeon::firstWhere('key', DungeonKey::ARA_KARA_CITY_OF_ECHOES->value);

        // Created inside the try so a failure halfway through still cleans up
        $futureSeason = null;

        try {
            $futureSeason = Season::create([
                'expansion_id'            => $expansion->id,
                'seasonal_affix_id'       => null,
                'index'                   => $currentSeason->index + 1,
                'start'                   => Carbon::now()->addDays(60)->toDateTimeString(),
                'presets'                 => 0,
                'affix_group_count'       => 8,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 25,
                'item_level_min'          => 240,
                'item_level_max'          => 300,
            ]);

            SeasonDungeon::create([
                'season_id'  => $futureSeason->id,
                'dungeon_id' => $futureSeasonDungeon->id,
            ]);

            // Act - resolved after seeding the season so the season service starts with a cold cache
            $dungeons = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($gameVersion);

            // Assert
            $this->assertEqualsCanonicalizing(
                $currentSeason->dungeons()->pluck('dungeons.id')->all(),
                $dungeons->pluck('id')->all(),
            );
            $this->assertNotContains($futureSeasonDungeon->id, $dungeons->pluck('id')->all());
        } finally {
            if ($futureSeason !== null) {
                foreach ($futureSeason->seasonDungeons as $seasonDungeon) {
                    $seasonDungeon->delete();
                }

                $futureSeason->delete();
            }
        }
    }
}
