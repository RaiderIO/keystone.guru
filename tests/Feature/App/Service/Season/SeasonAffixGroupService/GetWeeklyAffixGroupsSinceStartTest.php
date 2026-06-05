<?php

namespace App\Service\Season\SeasonAffixGroupService;

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
}
