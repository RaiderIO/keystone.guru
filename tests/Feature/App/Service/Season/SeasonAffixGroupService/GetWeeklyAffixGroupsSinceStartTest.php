<?php

namespace Tests\Feature\App\Service\Season\SeasonAffixGroupService;
use App\Models\AffixGroup\AffixGroup;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonAffixGroupService')]
#[Group('GetWeeklyAffixGroupsSinceStart')]
final class GetWeeklyAffixGroupsSinceStartTest extends PublicTestCase
{
    /**
     * TWW S2 (2025-03-03) has a preceding season (TWW S1) active at its HasStart date, so
     * getAffixGroupAt returns non-null and the weekly loop runs as expected.
     *
     * @throws Exception
     */
    #[Test]
    public function getWeeklyAffixGroupsSinceStart_GivenTwwS2WithUsRegion_ShouldReturnNonEmptyCollection(): void
    {
        // Arrange
        $service  = app(SeasonAffixGroupServiceInterface::class);
        $twwS2    = Season::findOrFail(Season::SEASON_TWW_S2);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = $service->getWeeklyAffixGroupsSinceStart($twwS2, $usRegion);

        // Assert
        $this->assertNotEmpty($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getWeeklyAffixGroupsSinceStart_GivenTwwS2_ShouldReturnWeeklyAffixGroupDtos(): void
    {
        // Arrange
        $service  = app(SeasonAffixGroupServiceInterface::class);
        $twwS2    = Season::findOrFail(Season::SEASON_TWW_S2);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = $service->getWeeklyAffixGroupsSinceStart($twwS2, $usRegion);

        // Assert
        foreach ($result as $entry) {
            $this->assertInstanceOf(WeeklyAffixGroup::class, $entry);
            $this->assertInstanceOf(AffixGroup::class, $entry->affixGroup);
            $this->assertGreaterThan(0, $entry->week);
            $this->assertInstanceOf(Carbon::class, $entry->date);
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getWeeklyAffixGroupsSinceStart_GivenTwwS2_ShouldReturnWeeksInSequentialOrder(): void
    {
        // Arrange
        $service  = app(SeasonAffixGroupServiceInterface::class);
        $twwS2    = Season::findOrFail(Season::SEASON_TWW_S2);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = $service->getWeeklyAffixGroupsSinceStart($twwS2, $usRegion);

        // Assert
        $expectedWeek = 1;
        foreach ($result as $entry) {
            $this->assertEquals($expectedWeek, $entry->week);
            $expectedWeek++;
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getWeeklyAffixGroupsSinceStart_GivenTwwS2_FirstWeekDateShouldMatchSeasonStart(): void
    {
        // Arrange
        $service  = app(SeasonAffixGroupServiceInterface::class);
        $twwS2    = Season::findOrFail(Season::SEASON_TWW_S2);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = $service->getWeeklyAffixGroupsSinceStart($twwS2, $usRegion);

        // Assert
        $firstEntry      = $result->first();
        $seasonStartDate = $twwS2->start($usRegion);

        $this->assertNotNull($firstEntry);
        $this->assertTrue($firstEntry->date->eq($seasonStartDate));
    }

    /**
     * Ground truth from the seeded season, not from the service: week k of the first rotation shows the affix group
     * at start_affix_group_index + k - 1.
     *
     * @throws Exception
     */
    #[Test]
    public function getWeeklyAffixGroupsSinceStart_givenTwwS2_returnsTheSeasonsRotationFromItsStartAffixGroup(): void
    {
        // Arrange
        $service  = app(SeasonAffixGroupServiceInterface::class);
        $twwS2    = Season::findOrFail(Season::SEASON_TWW_S2);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $this->assertGreaterThan(1, $twwS2->affix_group_count);
        $this->assertCount($twwS2->affix_group_count, $twwS2->affixGroups);

        // Act
        $result = $service->getWeeklyAffixGroupsSinceStart($twwS2, $usRegion);

        // Assert
        $this->assertGreaterThanOrEqual($twwS2->affix_group_count, $result->count());
        for ($week = 1; $week <= $twwS2->affix_group_count; $week++) {
            $expectedAffixGroup = $twwS2->affixGroups[($twwS2->start_affix_group_index + $week - 1) % $twwS2->affix_group_count];
            $this->assertSame(
                $expectedAffixGroup->id,
                $result[$week - 1]->affixGroup->id,
                sprintf('Week %d should show the affix group at index %d', $week, ($twwS2->start_affix_group_index + $week - 1) % $twwS2->affix_group_count),
            );
        }
    }
}
