<?php

namespace Tests\Feature\App\Service\Season\SeasonService;

use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetWeeklyPeriods')]
final class GetWeeklyPeriodsTest extends PublicTestCase
{
    #[Test]
    public function getWeeklyPeriods_givenSeasonThatHasEnded_returnsEveryWeekUpToTheNextSeason(): void
    {
        // Arrange
        $service         = app(SeasonServiceInterface::class);
        $usRegion        = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $season          = Season::findOrFail(Season::SEASON_BFA_S1);
        $nextSeasonStart = Season::findOrFail(Season::SEASON_BFA_S2)->start($usRegion);

        // Act
        $result = $service->getWeeklyPeriods($season, $usRegion);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertSame(1, $result->keys()->first());
        $this->assertSame(
            $usRegion->getKeystoneLeaderboardPeriod($season->start($usRegion)),
            $result->first(),
        );
        // The last week of the season must fall before the week the next season starts in
        $this->assertLessThan(
            $usRegion->getKeystoneLeaderboardPeriod($nextSeasonStart),
            $result->last(),
        );
    }

    #[Test]
    public function getWeeklyPeriods_givenSeasonThatHasEnded_returnsConsecutiveWeeksAndPeriods(): void
    {
        // Arrange
        $service  = app(SeasonServiceInterface::class);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $season   = Season::findOrFail(Season::SEASON_BFA_S1);

        // Act
        $result = $service->getWeeklyPeriods($season, $usRegion);

        // Assert
        $firstWeek   = $result->keys()->first();
        $firstPeriod = $result->first();

        foreach ($result as $week => $period) {
            $this->assertSame($firstPeriod + ($week - $firstWeek), $period);
        }
    }

    #[Test]
    public function getWeeklyPeriods_givenSeasonThatHasNotStartedYet_returnsNoWeeks(): void
    {
        // Arrange
        $service  = app(SeasonServiceInterface::class);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        /** @var Season|null $upcomingSeason */
        $upcomingSeason = Season::query()->where('start', '>', Carbon::now()->addWeeks(2))->orderBy('start')->first();

        if ($upcomingSeason === null) {
            $this->markTestSkipped('No upcoming season is seeded to test against.');
        }

        // Act
        $result = $service->getWeeklyPeriods($upcomingSeason, $usRegion);

        // Assert
        $this->assertTrue($result->isEmpty());
    }
}
