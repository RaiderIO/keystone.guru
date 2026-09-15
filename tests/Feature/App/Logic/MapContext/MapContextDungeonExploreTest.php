<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\Dtos\SeasonWeek;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Facades\Cache;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('MapContext')]
final class MapContextDungeonExploreTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // RemembersToFile writes to the `tmp_file` file store, which survives between test runs -
        // without this the assertions below could run against a payload built by older code.
        Cache::store('tmp_file')->flush();
    }

    /**
     * The heatmap's week filter turns its selection into the minPeriod/maxPeriod that are handed to Raider.IO
     * verbatim, so the periods it reads out of the map context must be the ones SeasonService computed - not a
     * week index added to the season's start period, which is off by one for any season whose raw start date
     * already falls on or after the region's reset (#4456).
     */
    #[Test]
    public function toArray_givenDungeonWithASeason_exposesTheSeasonWeeksTheServiceComputed(): void
    {
        // Arrange
        $seasonService = app(SeasonServiceInterface::class);
        $region        = GameServerRegion::getUserOrDefaultRegion();

        [$dungeon, $mappingVersion, $season] = $this->findDungeon(
            challengeMode: true,
            resolve:       static fn(Dungeon $dungeon) => $seasonService->getMostRecentSeasonForDungeon($dungeon),
        );

        $expected = $seasonService->getSeasonWeeks($season, $region)
            ->map(static fn(SeasonWeek $seasonWeek): array => [
                'week'   => $seasonWeek->week,
                'period' => $seasonWeek->period,
            ])
            ->values()
            ->all();

        // Act
        $seasonWeeks = app(MapContextServiceInterface::class)
            ->createMapContextDungeonExplore($dungeon, $mappingVersion, User::MAP_FACADE_STYLE_SPLIT_FLOORS)
            ->toArray()['seasonWeeks'];

        // Assert
        $this->assertNotEmpty($seasonWeeks);
        $this->assertSame($expected, $seasonWeeks);
    }

    /**
     * The week dropdown's options are built from the most recent season, and the server filters against that
     * same season - so the periods must be resolved from it too. getActiveSeason() prefers an upcoming season,
     * whose weeks are not in the dropdown at all.
     */
    #[Test]
    public function toArray_givenDungeonWithASeason_resolvesPeriodsAgainstTheMostRecentSeason(): void
    {
        // Arrange
        $seasonService = app(SeasonServiceInterface::class);
        $region        = GameServerRegion::getUserOrDefaultRegion();

        [$dungeon, $mappingVersion, $season] = $this->findDungeon(
            challengeMode: true,
            resolve:       static fn(Dungeon $dungeon) => $seasonService->getMostRecentSeasonForDungeon($dungeon),
        );

        $firstSeasonWeek = $seasonService->getSeasonWeeks($season, $region)->first();

        // Act
        $seasonWeeks = app(MapContextServiceInterface::class)
            ->createMapContextDungeonExplore($dungeon, $mappingVersion, User::MAP_FACADE_STYLE_SPLIT_FLOORS)
            ->toArray()['seasonWeeks'];

        // Assert
        $this->assertNotEmpty($seasonWeeks);
        $this->assertSame(1, $seasonWeeks[0]['week']);
        $this->assertSame($firstSeasonWeek->period, $seasonWeeks[0]['period']);
    }
}
