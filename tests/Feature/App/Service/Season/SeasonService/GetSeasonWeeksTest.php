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
#[Group('GetSeasonWeeks')]
final class GetSeasonWeeksTest extends PublicTestCase
{
    #[Test]
    public function getSeasonWeeks_givenSeasonThatHasEnded_returnsEveryWeekUpToTheNextSeason(): void
    {
        // Arrange
        $service         = app(SeasonServiceInterface::class);
        $usRegion        = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $season          = Season::findOrFail(Season::SEASON_BFA_S1);
        $nextSeasonStart = Season::findOrFail(Season::SEASON_BFA_S2)->start($usRegion);

        // Act
        $result = $service->getSeasonWeeks($season, $usRegion);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertSame(1, $result->keys()->first());
        $this->assertSame(
            $usRegion->getKeystoneLeaderboardPeriod($season->start($usRegion)->addDays(3)),
            $result->first()->period,
        );
        // The last week of the season must fall before the week the next season starts in
        $this->assertLessThan(
            $usRegion->getKeystoneLeaderboardPeriod($nextSeasonStart->addDays(3)),
            $result->last()->period,
        );
    }

    #[Test]
    public function getSeasonWeeks_givenSeasonThatHasEnded_returnsConsecutiveWeeksAndPeriods(): void
    {
        // Arrange
        $service  = app(SeasonServiceInterface::class);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $season   = Season::findOrFail(Season::SEASON_BFA_S1);

        // Act
        $result = $service->getSeasonWeeks($season, $usRegion);

        // Assert
        $firstPeriod = $result->first()->period;

        foreach ($result as $week => $seasonWeek) {
            $this->assertSame($week, $seasonWeek->week);
            $this->assertSame($firstPeriod + ($week - 1), $seasonWeek->period);
        }
    }

    #[Test]
    public function getSeasonWeeks_givenEverySeason_neverRepeatsAPeriodAcrossSeasons(): void
    {
        // Arrange
        $service  = app(SeasonServiceInterface::class);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $periods = $service->getAllSeasons()
            ->flatMap(static fn(Season $season) => $service->getSeasonWeeks($season, $usRegion)->pluck('period'));

        // Assert
        // A season's weeks are stepped in the region's timezone, where a DST change shifts the reset by an hour -
        // enough for a season's last week to claim the period its successor's first week already occupies
        $this->assertNotEmpty($periods);
        $this->assertSame($periods->count(), $periods->unique()->count());
    }

    #[Test]
    public function getSeasonWeeks_givenSeasonThatHasNotStartedYet_returnsNoWeeks(): void
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
        $result = $service->getSeasonWeeks($upcomingSeason, $usRegion);

        // Assert
        $this->assertTrue($result->isEmpty());
    }
}
