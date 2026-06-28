<?php

namespace App\Service\Season\SeasonAffixGroupService;

use App\Models\AffixGroup\AffixGroup;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonAffixGroupService')]
#[Group('GetDisplayedAffixGroups')]
final class GetDisplayedAffixGroupsTest extends PublicTestCase
{
    /**
     * The War Within season 1: affix_group_count 8, start_affix_group_index 3. It sits in the middle of the
     * seeded history so a window around it never runs off either end.
     */
    private const string TEST_SEASON_START = '2024-09-16 00:00:00';

    /**
     * The War Within season 2: the season directly following the test season, used to assert cross-season
     * spillover behaviour.
     */
    private const string NEXT_SEASON_START = '2025-03-03 00:00:00';

    /**
     * Dragonflight season 4: affix_group_count 10. It directly precedes the test season (which has count 8),
     * so it is used to assert behaviour across an affix_group_count change boundary.
     */
    private const string PREVIOUS_SEASON_START = '2024-04-22 00:00:00';

    #[\Override]
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_givenOffsetZero_returnsFullIterationPlusTwoSpilloverWeeks(): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);
        $region  = GameServerRegion::getUserOrDefaultRegion();
        $season  = $this->getTestSeason();
        Carbon::setTestNow($season->start($region)->copy()->addWeeks(5)->addHours(1));

        // Act
        $result = $service->getDisplayedAffixGroups(0);

        // Assert
        $this->assertCount($season->affix_group_count + 2, $result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('getDisplayedAffixGroups_offsetDataProvider')]
    public function getDisplayedAffixGroups_givenSmallOffset_returnsFullIterationPlusTwoSpilloverWeeks(int $offset): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);
        $region  = GameServerRegion::getUserOrDefaultRegion();
        $season  = $this->getTestSeason();
        Carbon::setTestNow($season->start($region)->copy()->addWeeks(5)->addHours(1));

        // Act
        $result = $service->getDisplayedAffixGroups($offset);

        // Assert
        $this->assertCount($season->affix_group_count + 2, $result);
    }

    /**
     * @return array<string, list<int>>
     */
    public static function getDisplayedAffixGroups_offsetDataProvider(): array
    {
        return [
            'previous iteration' => [-1],
            'next iteration'     => [1],
        ];
    }

    /**
     * The actual regression: as weeks pass, the current week must walk through the list (and never be pinned
     * to the last row). At week k of the iteration it must sit at row index k + 1 (row 0 is always last week).
     *
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_asWeeksPass_currentWeekWalksThroughTheList(): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);
        $region  = GameServerRegion::getUserOrDefaultRegion();
        $season  = $this->getTestSeason();

        for ($week = 0; $week < $season->affix_group_count; $week++) {
            $currentWeekStart = $season->start($region)->copy()->addWeeks($week);
            Carbon::setTestNow($currentWeekStart->copy()->addHours(1));

            // Act
            $result = $service->getDisplayedAffixGroups(0);

            // Assert - the row whose date matches the current week sits at index week + 1
            $currentWeekIndex = $result->search(
                static fn(array $entry): bool => $entry['date_start']->equalTo($currentWeekStart),
            );

            $this->assertSame(
                $week + 1,
                $currentWeekIndex,
                sprintf('Week %d of the iteration should be displayed at row index %d', $week, $week + 1),
            );
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_givenOffsetZero_firstAndLastRowsAreTheSpilloverWeeks(): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);
        $region  = GameServerRegion::getUserOrDefaultRegion();
        $season  = $this->getTestSeason();
        Carbon::setTestNow($season->start($region)->copy()->addWeeks(5)->addHours(1));

        // Act
        $result = $service->getDisplayedAffixGroups(0);

        // Assert - iteration 0 spans weeks 0..count-1, so the spillover is week -1 and week count
        $iterationStart = $season->start($region);
        $first          = $result->first();
        $last           = $result->last();

        $this->assertTrue(
            $first['date_start']->equalTo($iterationStart->copy()->subWeek()),
            'First row should be the week before the iteration',
        );
        $this->assertTrue(
            $last['date_start']->equalTo($iterationStart->copy()->addWeeks($season->affix_group_count)),
            'Last row should be the week after the iteration',
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_givenMidIteration_currentWeekRowMatchesCurrentAffixGroup(): void
    {
        // Arrange
        $service          = app(SeasonAffixGroupServiceInterface::class);
        $region           = GameServerRegion::getUserOrDefaultRegion();
        $season           = $this->getTestSeason();
        $currentWeekStart = $season->start($region)->copy()->addWeeks(4);
        Carbon::setTestNow($currentWeekStart->copy()->addHours(1));

        // Act
        $result      = $service->getDisplayedAffixGroups(0);
        $currentWeek = $result->first(
            static fn(array $entry): bool => $entry['date_start']->equalTo($currentWeekStart),
        );

        // Assert - the displayed current week equals the authoritative "current affix" shown on the page
        $currentAffixGroup = $service->getCurrentAffixGroup($season);
        $this->assertNotNull($currentAffixGroup);
        $this->assertSame($currentAffixGroup->id, $currentWeek['affix_group']->id);
    }

    /**
     * Ground truth derived from the seeder (not from the implementation's own output): on the very first week
     * of a season the affix index equals the season's start_affix_group_index.
     *
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_atSeasonStart_currentWeekAffixMatchesStartAffixGroupIndex(): void
    {
        // Arrange
        $service       = app(SeasonAffixGroupServiceInterface::class);
        $region        = GameServerRegion::getUserOrDefaultRegion();
        $season        = $this->getTestSeason();
        $weekZeroStart = $season->start($region);
        Carbon::setTestNow($weekZeroStart->copy()->addHours(1));

        // Act
        $result   = $service->getDisplayedAffixGroups(0);
        $weekZero = $result->first(
            static fn(array $entry): bool => $entry['date_start']->equalTo($weekZeroStart),
        );

        // Assert
        $expectedAffixGroup = $season->affixGroups[$season->start_affix_group_index];
        $this->assertSame($expectedAffixGroup->id, $weekZero['affix_group']->id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_atSeasonStart_previousWeekRowBelongsToThePreviousSeason(): void
    {
        // Arrange
        $service        = app(SeasonAffixGroupServiceInterface::class);
        $region         = GameServerRegion::getUserOrDefaultRegion();
        $season         = $this->getNextSeason();
        $previousSeason = $this->getTestSeason();
        Carbon::setTestNow($season->start($region)->copy()->addHours(1));

        // Act
        $result   = $service->getDisplayedAffixGroups(0);
        $firstRow = $result->first();

        // Assert - the spillover week before this season's first week belongs to the previous season
        $this->assertTrue($firstRow['date_start']->lt($season->start($region)));
        $this->assertSame($previousSeason->id, $firstRow['affix_group']->season_id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_givenOffsetZero_returnsEntriesWithRequiredKeys(): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);
        $region  = GameServerRegion::getUserOrDefaultRegion();
        $season  = $this->getTestSeason();
        Carbon::setTestNow($season->start($region)->copy()->addWeeks(5)->addHours(1));

        // Act
        $result = $service->getDisplayedAffixGroups(0);

        // Assert
        foreach ($result as $entry) {
            $this->assertArrayHasKey('date_start', $entry);
            $this->assertArrayHasKey('affix_group', $entry);
            $this->assertInstanceOf(Carbon::class, $entry['date_start']);
            $this->assertInstanceOf(AffixGroup::class, $entry['affix_group']);
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_givenNextIteration_startsLaterThanCurrentIteration(): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);
        $region  = GameServerRegion::getUserOrDefaultRegion();
        $season  = $this->getTestSeason();
        Carbon::setTestNow($season->start($region)->copy()->addWeeks(5)->addHours(1));

        // Act
        $resultCurrent = $service->getDisplayedAffixGroups(0);
        $resultNext    = $service->getDisplayedAffixGroups(1);

        // Assert
        $this->assertTrue($resultNext->first()['date_start']->gt($resultCurrent->first()['date_start']));
    }

    /**
     * Guards the live page path (`Carbon::now()` -> the newest populated season), which the fixed-date tests
     * above do not exercise: the list must never be empty and the current week must match the headline affix.
     *
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_givenLatestSeason_returnsNonEmptyListMatchingCurrentAffixGroup(): void
    {
        // Arrange
        $service          = app(SeasonAffixGroupServiceInterface::class);
        $region           = GameServerRegion::getUserOrDefaultRegion();
        $season           = $this->getCurrentPopulatedSeason();
        $currentWeekStart = $season->start($region)->copy()->addWeeks(3);
        Carbon::setTestNow($currentWeekStart->copy()->addHours(1));

        // Act
        $result      = $service->getDisplayedAffixGroups(0);
        $currentWeek = $result->first(
            static fn(array $entry): bool => $entry['date_start']->equalTo($currentWeekStart),
        );

        // Assert
        $this->assertCount($season->affix_group_count + 2, $result);
        $currentAffixGroup = $service->getCurrentAffixGroup($season);
        $this->assertNotNull($currentAffixGroup);
        $this->assertSame($currentAffixGroup->id, $currentWeek['affix_group']->id);
    }

    /**
     * The spillover into the previous season must use that season's own affix_group_count, not the current
     * season's, when the two differ (here: previous count 10, current count 8).
     *
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_acrossAffixGroupCountChange_usesEachSeasonsOwnCount(): void
    {
        // Arrange
        $service        = app(SeasonAffixGroupServiceInterface::class);
        $region         = GameServerRegion::getUserOrDefaultRegion();
        $season         = $this->getTestSeason();
        $previousSeason = $this->getPreviousSeason();
        $this->assertNotSame(
            $previousSeason->affix_group_count,
            $season->affix_group_count,
            'This test requires the two seasons to have different affix group counts',
        );
        Carbon::setTestNow($season->start($region)->copy()->addHours(1));

        // Act - the row-0 spillover week falls in the previous (different-count) season
        $result   = $service->getDisplayedAffixGroups(0);
        $firstRow = $result->first();

        // Assert - the spillover affix is resolved against the previous season using its own count
        $this->assertSame($previousSeason->id, $firstRow['affix_group']->season_id);

        $elapsedWeeks       = (int)$previousSeason->start($region)->diffInWeeks($firstRow['date_start'], true);
        $expectedIndex      = ($previousSeason->start_affix_group_index + $elapsedWeeks) % $previousSeason->affix_group_count;
        $expectedAffixGroup = $previousSeason->affixGroups[$expectedIndex];
        $this->assertSame($expectedAffixGroup->id, $firstRow['affix_group']->id);
    }

    private function getTestSeason(): Season
    {
        return Season::where('start', self::TEST_SEASON_START)->firstOrFail();
    }

    private function getPreviousSeason(): Season
    {
        return Season::where('start', self::PREVIOUS_SEASON_START)->firstOrFail();
    }

    private function getCurrentPopulatedSeason(): Season
    {
        $region = GameServerRegion::getUserOrDefaultRegion();
        $now    = Carbon::now();

        return Season::selectRaw('seasons.*')
            ->with(['expansion', 'expansion.timewalkingEvent', 'affixGroups'])
            ->leftJoin('timewalking_events', 'timewalking_events.expansion_id', 'seasons.expansion_id')
            ->whereNull('timewalking_events.id')
            ->orderBy('start', 'desc')
            ->get()
            ->first(static fn(Season $season): bool => $season->affix_group_count > 0
                && $season->affixGroups->isNotEmpty()
                && $season->start($region)->lte($now))
            ?? throw new Exception('No current populated season found in the seeded database');
    }

    private function getNextSeason(): Season
    {
        return Season::where('start', self::NEXT_SEASON_START)->firstOrFail();
    }
}
