<?php

namespace Tests\Feature\App\Service\Season\SeasonService;
use App\Models\Expansion;
use App\Models\Season;
use App\Models\Timewalking\TimewalkingEvent;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetFirstSeason')]
final class GetFirstSeasonTest extends PublicTestCase
{
    #[Test]
    public function getFirstSeason_ShouldReturnBfaS1AsEarliestSeason(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getFirstSeason();

        // Assert - BFA S1 is the first season (2018-09-04), const id = 1
        $this->assertInstanceOf(Season::class, $result);
        $this->assertEquals(Season::SEASON_BFA_S1, $result->id);
    }

    #[Test]
    public function getFirstSeason_givenCalledBefore_runsNoQuery(): void
    {
        // Arrange
        $service  = $this->newSeasonService();
        $expected = $service->getFirstSeason();
        $queries  = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        // Act
        $result = $service->getFirstSeason();

        // Assert
        $this->assertSame(0, $queries);
        $this->assertSame($expected->id, $result->id);
    }

    #[Test]
    public function getFirstSeason_givenTheEarliestSeasonsExpansionHasATimewalkingEvent_returnsTheFirstSeasonOfAnotherExpansion(): void
    {
        // Arrange
        $bfaExpansion     = Expansion::where('key', Expansion::EXPANSION_BFA)->firstOrFail();
        $timewalkingEvent = null;

        try {
            $timewalkingEvent = $this->createTimewalkingEvent($bfaExpansion);

            // Act
            $result = $this->newSeasonService()->getFirstSeason();

            // Assert
            $this->assertSame(Season::SEASON_SL_S1, $result->id);
        } finally {
            $timewalkingEvent?->delete();
        }
    }

    /**
     * A fresh instance, so no season memoised by an earlier call answers.
     */
    private function newSeasonService(): SeasonService
    {
        return new SeasonService(app(ExpansionService::class), app(SeasonRepositoryInterface::class));
    }

    private function createTimewalkingEvent(Expansion $expansion): TimewalkingEvent
    {
        return TimewalkingEvent::forceCreate([
            'expansion_id'         => $expansion->id,
            'key'                  => 'season_service_test',
            'name'                 => 'Season service test',
            'start'                => Carbon::create(2018, 1, 2),
            'start_duration_weeks' => 1,
            'week_interval'        => 1,
        ]);
    }
}
