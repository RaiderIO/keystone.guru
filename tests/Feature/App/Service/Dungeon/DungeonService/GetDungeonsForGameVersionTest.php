<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\DungeonService;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
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
     * Scenario: a season has been seeded before its start date, so getNextSeason() returns a season
     * instead of null. Reading its dungeons lazily throws, and because HeaderComposer calls this for
     * the site header that takes down every page rendering layouts/sitepage.blade.php (#3746).
     */
    #[Test]
    public function getDungeonsForGameVersion_givenAnUpcomingSeason_returnsThatSeasonsDungeons(): void
    {
        // Arrange - both seasons come out of a multi-row result on purpose. That is what
        // SeasonService hands back, and preventLazyLoading only enforces on models loaded as part of
        // a collection - a single-model load is exempt, which is why the current-season fallback
        // never tripped this.
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
            $nextSeason->dungeons()->pluck('dungeons.id')->all(),
            $dungeons->pluck('id')->all(),
        );
    }

    /**
     * Scenario: no season has been seeded ahead of its start date, so the method falls back to the
     * current season. In production that season is loaded as a single model and the guard exempts
     * it, which is why this path never crashed - it is loaded as a collection here so the fallback
     * is covered by the same guarantee as the branch above.
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
}
