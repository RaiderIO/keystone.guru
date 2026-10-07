<?php

namespace Tests\Feature\App\Models\Trait;

use App\Models\GameServerRegion;
use App\Models\Season;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('HasStart')]
final class HasStartTest extends PublicTestCase
{
    /**
     * The reset_day_offset values are defined relative to a Monday week start. A season whose start is stored
     * on a Monday must therefore resolve to the region's actual reset day - Tuesday for the Americas (offset 1)
     * and Wednesday for Europe (offset 2) - and not a day earlier.
     */
    #[Test]
    public function start_givenMondayStoredStart_resolvesToTheRegionsResetDay(): void
    {
        // Arrange - 2026-03-02 is a Monday
        $season = new Season([
            'start' => Carbon::create(2026, 3, 2, 0, 0, 0, 'UTC'),
        ]);
        $americas = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();
        $europe   = GameServerRegion::where('key', GameServerRegion::EUROPE)->firstOrFail();

        // Act
        $americasStart = $season->start($americas);
        $europeStart   = $season->start($europe);

        // Assert
        $this->assertTrue($americasStart->isTuesday(), 'Americas reset should fall on a Tuesday');
        $this->assertTrue($europeStart->isWednesday(), 'Europe reset should fall on a Wednesday');
    }

    #[Test]
    public function start_givenMidWeekStoredStart_resolvesToThatWeeksResetDay(): void
    {
        // Arrange - 2026-03-05 is a Thursday, so its week started on Monday 2026-03-02
        $season = new Season([
            'start' => Carbon::create(2026, 3, 5, 0, 0, 0, 'UTC'),
        ]);
        $americas = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();
        $europe   = GameServerRegion::where('key', GameServerRegion::EUROPE)->firstOrFail();

        // Act
        $americasStart = $season->start($americas);
        $europeStart   = $season->start($europe);

        // Assert
        $this->assertSame('2026-03-03', $americasStart->toDateString());
        $this->assertSame('2026-03-04', $europeStart->toDateString());
    }
}
