<?php

namespace App\Service\Season\SeasonService;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetSeasonAt')]
final class GetSeasonAtTest extends PublicTestCase
{
    #[Test]
    public function getSeasonAt_GivenDateDuringBfaS1_ShouldReturnBfaS1(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        // BFA S1 started 2018-09-04, BFA S2 started 2019-01-23
        $date = Carbon::create(2018, 11, 1, 0, 0, 0, 'UTC');

        // Act
        $result = $service->getSeasonAt($date, $bfaExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_BFA_S1, $result->id);
    }

    #[Test]
    public function getSeasonAt_GivenDateBeforeAllSeasons_ShouldReturnNull(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $date         = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');

        // Act
        $result = $service->getSeasonAt($date, $bfaExpansion, $usRegion);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getSeasonAt_GivenDateDuringBfaS2_ShouldReturnBfaS2(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        // BFA S2 started 2019-01-23, BFA S3 started after
        $date = Carbon::create(2019, 3, 15, 0, 0, 0, 'UTC');

        // Act
        $result = $service->getSeasonAt($date, $bfaExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_BFA_S2, $result->id);
    }

    #[Test]
    public function getSeasonAt_GivenSeasonStartDate_ShouldReturnThatSeason(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        // US region: reset_day_offset=1, reset_hours_offset=15
        // BFA S1 start='2018-09-04', after offsets => 2018-09-05 15:00:00 UTC
        $date = Carbon::create(2018, 9, 4)
            ->addDays($usRegion->reset_day_offset)->addHours($usRegion->reset_hours_offset + 1);

        // Act
        $result = $service->getSeasonAt($date, $bfaExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_BFA_S1, $result->id);
    }
}
