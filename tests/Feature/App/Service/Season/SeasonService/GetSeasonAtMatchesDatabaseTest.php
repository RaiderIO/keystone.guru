<?php

namespace Tests\Feature\App\Service\Season\SeasonService;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards #4587: getSeasonAt() resolves the date against the expansion's seasons in memory instead of
 * asking the database once per date. This pins the PHP comparison to the SQL it replaced - if the two
 * ever disagree on a reset boundary, every affix group derived from a date silently shifts a season.
 */
#[Group('SeasonService')]
#[Group('GetSeasonAt')]
final class GetSeasonAtMatchesDatabaseTest extends PublicTestCase
{
    #[Test]
    public function getSeasonAt_givenEverySeasonBoundaryInEveryRegion_returnsWhatTheDatabaseComparisonReturns(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);
        $regions = GameServerRegion::all();
        $seasons = Season::orderBy('start')->get();

        $this->assertNotEmpty($regions);
        $this->assertNotEmpty($seasons);

        $comparisons = 0;

        foreach ($seasons as $season) {
            $expansion = Expansion::findOrFail($season->expansion_id);

            foreach ($regions as $region) {
                $resetMoment = $season->start->copy()
                    ->addDays($region->reset_day_offset)
                    ->addHours($region->reset_hours_offset);

                // One second either side of the reset moment, and the moment itself - the boundaries
                // are the only dates where an off-by-one in the arithmetic shows up
                foreach ([-1, 0, 1] as $secondsOffset) {
                    $date = $resetMoment->copy()->addSeconds($secondsOffset);

                    // Act
                    $result = $service->getSeasonAt($date, $expansion, $region);

                    // Assert
                    $this->assertSame(
                        $this->getSeasonAtThroughDatabase($date, $expansion, $region)?->id,
                        $result?->id,
                        sprintf(
                            'Season %d, region %s, %+d second(s) from its reset moment',
                            $season->id,
                            $region->short,
                            $secondsOffset,
                        ),
                    );

                    $comparisons++;
                }
            }
        }

        $this->assertGreaterThan(0, $comparisons);
    }

    /**
     * The query getSeasonAt() ran before #4587, kept here as the reference implementation.
     */
    private function getSeasonAtThroughDatabase(Carbon $date, Expansion $expansion, GameServerRegion $region): ?Season
    {
        /** @var Season|null $season */
        $season = Season::whereRaw(
            'DATE_ADD(DATE_ADD(`start`, INTERVAL ? day), INTERVAL ? hour) <= ?',
            [
                $region->reset_day_offset,
                $region->reset_hours_offset,
                $date->copy()->setTimezone('UTC')->toDateTimeString(),
            ],
        )
            ->where('expansion_id', $expansion->id)
            ->orderBy('start', 'desc')
            ->first();

        return $season;
    }
}
