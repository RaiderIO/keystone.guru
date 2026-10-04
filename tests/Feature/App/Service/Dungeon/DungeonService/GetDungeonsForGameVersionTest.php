<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\DungeonSelectorGroup;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Season;
use App\Models\SeasonDungeon;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\PageViewCountRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\DungeonService;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonService')]
#[Group('GetDungeonsForGameVersion')]
final class GetDungeonsForGameVersionTest extends PublicTestCase
{
    use CreatesSeason;

    private const string DUNGEON_KEY = 'get_dungeons_for_game_version_test';

    /**
     * Two seasons of the test's own with disjoint dungeons, read back in one query so both come out of a
     * multi-row result.
     *
     * @return array{0: Season, 1: Season} The current season, then the next one.
     */
    private function createCurrentAndNextSeason(): array
    {
        $dungeonIds = Dungeon::query()->active()->orderBy('id')->limit(4)->pluck('id')->all();
        $this->assertCount(4, $dungeonIds);

        $currentSeasonId = $this->createSeason(['start' => now()->subMonth()], array_slice($dungeonIds, 0, 2))->id;
        $nextSeasonId    = $this->createSeason(['start' => now()->addMonth()], array_slice($dungeonIds, 2, 2))->id;

        $seasons = Season::query()->whereKey([$currentSeasonId, $nextSeasonId])->get()->keyBy('id');

        return [$seasons->get($currentSeasonId), $seasons->get($nextSeasonId)];
    }

    /**
     * An active, non-raid Classic dungeon of the test's own, without any mapping version.
     */
    private function createClassicDungeon(): Dungeon
    {
        return Dungeon::create([
            'expansion_id'     => Expansion::ALL[Expansion::EXPANSION_CLASSIC],
            'active'           => 1,
            'raid'             => 0,
            'speedrun_enabled' => false,
            'zone_id'          => 0,
            'map_id'           => 0,
            'mdt_id'           => 0,
            'name'             => 'dungeons.classic.deadmines.name',
            'key'              => self::DUNGEON_KEY,
            'slug'             => self::DUNGEON_KEY,
        ]);
    }

    private function createMappingVersion(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $gameVersion->id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => 1,
            'enemy_forces_required'           => 0,
            'enemy_forces_required_teeming'   => null,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 1800,
        ]);
    }

    private function deleteDungeon(?Dungeon $dungeon): void
    {
        if ($dungeon === null) {
            return;
        }

        MappingVersion::query()->where('dungeon_id', $dungeon->id)->delete();
        Dungeon::query()->whereKey($dungeon->id)->delete();
    }

    /**
     * Builds the service with everything but the season service and the dungeon repository stubbed out -
     * those are the only collaborators this method's behaviour depends on.
     */
    private function buildService(SeasonServiceInterface $seasonService): DungeonService
    {
        return new DungeonService(
            $this->createMockPublic(CookieServiceInterface::class),
            $seasonService,
            $this->createMockPublic(DungeonServiceLoggingInterface::class),
            $this->createMockPublic(GameVersionServiceInterface::class),
            app(DungeonRepositoryInterface::class),
            $this->createMockPublic(PageViewCountRepositoryInterface::class),
            $this->createMockPublic(CacheServiceInterface::class),
        );
    }

    private function createSeasonlessSeasonService(): SeasonServiceInterface
    {
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn(null);
        $seasonService->method('getNextSeason')->willReturn(null);

        return $seasonService;
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
        [$currentSeason, $nextSeason] = $this->createCurrentAndNextSeason();

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
        [$currentSeason] = $this->createCurrentAndNextSeason();

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
     * The header asks on every page: a season handed over with its dungeons loaded must cost no query.
     */
    #[Test]
    public function getDungeonsForGameVersion_givenACurrentSeasonWithItsDungeonsLoaded_readsNoDungeons(): void
    {
        // Arrange
        $currentSeason = Season::with('dungeons')->findOrFail(Season::SEASON_SL_S4);

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($currentSeason);

        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $service     = $this->buildService($seasonService);

        $dungeonQueries = 0;
        DB::listen(static function (QueryExecuted $query) use (&$dungeonQueries): void {
            if (str_contains($query->sql, 'from `dungeons`')) {
                $dungeonQueries++;
            }
        });

        // Act
        $dungeons = $service->getDungeonsForGameVersion($gameVersion);

        // Assert
        $this->assertSame(0, $dungeonQueries);
        $this->assertEqualsCanonicalizing(
            $currentSeason->dungeons->pluck('id')->all(),
            $dungeons->pluck('id')->all(),
        );
    }

    /**
     * Scenario: a game version without seasons - every active dungeon and raid mapped for it is relevant.
     */
    #[Test]
    public function getDungeonsForGameVersion_givenNoCurrentSeason_returnsEveryActiveDungeonAndRaidMappedForTheGameVersion(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $expected    = Dungeon::query()->active()->forGameVersion($gameVersion)->pluck('id')->all();

        // Act
        $dungeons = $this->buildService($this->createSeasonlessSeasonService())->getDungeonsForGameVersion($gameVersion);

        // Assert
        $this->assertEqualsCanonicalizing($expected, $dungeons->pluck('id')->all());
        $this->assertTrue($dungeons->contains(static fn(Dungeon $dungeon) => $dungeon->raid), 'Expected Classic Era raids');
        $this->assertTrue($dungeons->contains(static fn(Dungeon $dungeon) => !$dungeon->raid), 'Expected Classic Era dungeons');
    }

    /**
     * Scenario: an expansion's dungeon that was never mapped cannot be opened, so it must not be offered.
     */
    #[Test]
    public function getDungeonsForGameVersion_givenADungeonWithoutMappingVersions_doesNotReturnIt(): void
    {
        $dungeon = null;

        try {
            // Arrange
            $dungeon     = $this->createClassicDungeon();
            $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

            // Act
            $dungeons = $this->buildService($this->createSeasonlessSeasonService())->getDungeonsForGameVersion($gameVersion);

            // Assert
            $this->assertNotContains($dungeon->id, $dungeons->pluck('id')->all());
            $this->assertNotEmpty($dungeons);
        } finally {
            $this->deleteDungeon($dungeon);
        }
    }

    /**
     * Scenario: Classic Era's expansion holds dungeons that were never mapped for the Classic Era game version.
     */
    #[Test]
    public function getDungeonsForGameVersion_givenNoCurrentSeason_excludesDungeonsWithoutAMappingVersionForTheGameVersion(): void
    {
        // Arrange
        $gameVersion      = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $unmappedDungeons = $gameVersion->expansion->dungeons()
            ->whereDoesntHave('mappingVersions', static fn($query) => $query->where('game_version_id', $gameVersion->id))
            ->pluck('id')
            ->all();

        $this->assertNotEmpty($unmappedDungeons, 'Need a Classic Era dungeon without a Classic Era mapping version');

        // Act
        $dungeons = $this->buildService($this->createSeasonlessSeasonService())->getDungeonsForGameVersion($gameVersion);

        // Assert
        $this->assertNotEmpty($dungeons);
        $this->assertSame([], array_values(array_intersect($unmappedDungeons, $dungeons->pluck('id')->all())));
    }

    #[Test]
    public function getDungeonsForGameVersion_givenNoCurrentSeason_excludesInactiveDungeons(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        /** @var Dungeon $deactivatedDungeon */
        $deactivatedDungeon = Dungeon::query()->active()->forGameVersion($gameVersion)->orderBy('id')->firstOrFail();

        try {
            $deactivatedDungeon->update(['active' => false]);

            // Act
            $dungeons = $this->buildService($this->createSeasonlessSeasonService())->getDungeonsForGameVersion($gameVersion);

            // Assert
            $this->assertNotEmpty($dungeons);
            $this->assertNotContains($deactivatedDungeon->id, $dungeons->pluck('id')->all());
        } finally {
            $deactivatedDungeon->update(['active' => true]);
        }
    }

    #[Test]
    public function getDungeonsForGameVersion_givenNoCurrentSeason_ordersBySelectorGroupThenByName(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

        // Act
        $dungeons = $this->buildService($this->createSeasonlessSeasonService())->getDungeonsForGameVersion($gameVersion);

        // Assert
        $this->assertSame(range(0, $dungeons->count() - 1), $dungeons->keys()->all());

        $sortKeys = $dungeons->map(static fn(Dungeon $dungeon) => [
            $dungeon->getSelectorGroup()->sortOrder(),
            strtolower(Str::ascii(__($dungeon->name))),
        ])->all();
        $expectedSortKeys = $sortKeys;
        usort($expectedSortKeys, static fn(array $a, array $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        $this->assertSame($expectedSortKeys, $sortKeys);
    }

    #[Test]
    public function getDungeonsForGameVersion_givenNoCurrentSeasonAndAMappedContinent_listsTheContinentFirst(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_FOREVER);
        $kalimdor    = Dungeon::firstWhere('key', DungeonKey::KALIMDOR->value);

        $this->assertTrue(
            $kalimdor->active && $kalimdor->getCurrentMappingVersionForGameVersion($gameVersion) !== null,
            'Need Kalimdor active and mapped for Forever',
        );

        // Act
        $dungeons = $this->buildService($this->createSeasonlessSeasonService())->getDungeonsForGameVersion($gameVersion);

        // Assert
        $this->assertSame(DungeonSelectorGroup::WORLD, $dungeons->first()->getSelectorGroup());
        $this->assertContains($kalimdor->id, $dungeons->pluck('id')->all());
    }

    #[Test]
    public function getDungeonsForGameVersion_givenForeverAndTbcClassic_listsTheTbcRaidsUnderTbcClassicOnly(): void
    {
        // Arrange
        $foreverGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_FOREVER);
        $tbcGameVersion     = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_TBC);
        $tbcRaidIds         = Dungeon::query()
            ->active()
            ->where('raid', true)
            ->where('expansion_id', Expansion::ALL[Expansion::EXPANSION_TBC])
            ->pluck('id')
            ->all();
        $this->assertNotEmpty($tbcRaidIds);

        $service = $this->buildService($this->createSeasonlessSeasonService());

        // Act
        $foreverDungeonIds = $service->getDungeonsForGameVersion($foreverGameVersion)->pluck('id')->all();
        $tbcDungeonIds     = $service->getDungeonsForGameVersion($tbcGameVersion)->pluck('id')->all();

        // Assert
        $this->assertNotEmpty($foreverDungeonIds);
        $this->assertSame([], array_values(array_intersect($tbcRaidIds, $foreverDungeonIds)));
        $this->assertSame([], array_values(array_diff($tbcRaidIds, $tbcDungeonIds)));
    }

    #[Test]
    public function getDungeonsForGameVersion_givenADungeonWithAMappingVersionForTheGameVersion_returnsIt(): void
    {
        $dungeon = null;

        try {
            // Arrange
            $dungeon     = $this->createClassicDungeon();
            $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
            $this->createMappingVersion($dungeon, $gameVersion);

            // Act
            $dungeons = $this->buildService($this->createSeasonlessSeasonService())->getDungeonsForGameVersion($gameVersion);

            // Assert
            $this->assertContains($dungeon->id, $dungeons->pluck('id')->all());
        } finally {
            $this->deleteDungeon($dungeon);
        }
    }

    /**
     * Scenario: Eastern Kingdoms and Kalimdor belong to the Classic expansion but are only mapped for WoW:
     * Forever, which shares that expansion - they showed up under Classic Era anyway.
     */
    #[Test]
    public function getDungeonsForGameVersion_givenADungeonMappedOnlyForAnotherGameVersionOfTheSameExpansion_doesNotReturnIt(): void
    {
        $dungeon = null;

        try {
            // Arrange
            $dungeon            = $this->createClassicDungeon();
            $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
            $foreverGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_FOREVER);
            $this->assertSame($classicGameVersion->expansion_id, $foreverGameVersion->expansion_id);
            $this->createMappingVersion($dungeon, $foreverGameVersion);

            $service = $this->buildService($this->createSeasonlessSeasonService());

            // Act
            $classicDungeons = $service->getDungeonsForGameVersion($classicGameVersion);
            $foreverDungeons = $service->getDungeonsForGameVersion($foreverGameVersion);

            // Assert
            $this->assertNotContains($dungeon->id, $classicDungeons->pluck('id')->all());
            $this->assertContains($dungeon->id, $foreverDungeons->pluck('id')->all());
        } finally {
            $this->deleteDungeon($dungeon);
        }
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
