<?php

namespace Tests\Feature\App\Service\Season\SeasonService;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetSeasonAt')]
final class GetSeasonAtTest extends PublicTestCase
{
    use CreatesSeason;

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
        // BFA S1 start='2018-09-04', a Tuesday: Monday 2018-09-03 after offsets => 2018-09-04 15:00:00 UTC
        $date = Carbon::create(2018, 9, 4)
            ->addDays($usRegion->reset_day_offset)->addHours($usRegion->reset_hours_offset + 1);

        // Act
        $result = $service->getSeasonAt($date, $bfaExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_BFA_S1, $result->id);
    }

    #[Test]
    public function getSeasonAt_givenFirstResetOfMidWeekStartingSeason_returnsThatSeason(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $twwExpansion = Expansion::where('shortname', Expansion::EXPANSION_TWW)->firstOrFail();
        // Wednesday 2030-01-09: its first reset is counted from Monday 2030-01-07
        $season = $this->createSeason(['expansion_id' => $twwExpansion->id, 'start' => '2030-01-09 00:00:00']);
        $date   = Carbon::create(2030, 1, 7, 0, 0, 0, 'UTC')
            ->addDays($usRegion->reset_day_offset)
            ->addHours($usRegion->reset_hours_offset);

        // Act
        $result = $service->getSeasonAt($date, $season->expansion, $usRegion);

        // Assert
        $this->assertSame($season->id, $result?->id);
    }

    #[Test]
    public function getSeasonAt_givenMomentBeforeFirstResetOfMidWeekStartingSeason_returnsPreviousSeason(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $twwExpansion = Expansion::where('shortname', Expansion::EXPANSION_TWW)->firstOrFail();
        $season       = $this->createSeason(['expansion_id' => $twwExpansion->id, 'start' => '2030-01-09 00:00:00']);
        $date         = Carbon::create(2030, 1, 7, 0, 0, 0, 'UTC')
            ->addDays($usRegion->reset_day_offset)
            ->addHours($usRegion->reset_hours_offset)
            ->subSecond();

        // Act
        $result = $service->getSeasonAt($date, $season->expansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertNotSame($season->id, $result->id);
    }
}
