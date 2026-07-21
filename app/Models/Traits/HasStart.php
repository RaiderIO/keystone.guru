<?php

namespace App\Models\Traits;

use App\Models\GameServerRegion;
use App\Traits\UserCurrentTime;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $start
 */
trait HasStart
{
    use UserCurrentTime;

    /**
     * @return Carbon The start date of this object.
     */
    public function start(?GameServerRegion $region = null): Carbon
    {
        $start      = Carbon::createFromTimeString($this->start, 'UTC');
        $userRegion = $region === null ? GameServerRegion::getUserOrDefaultRegion() : null;

        // The reset_day_offset values are defined relative to a Monday week start, so normalise to Monday
        // explicitly - relying on the default would use the locale's first day of week (Sunday here), which
        // shifts every reset a day too early.
        $start->startOfWeek(Carbon::MONDAY);
        $start->addDays(($region ?? $userRegion)->reset_day_offset);
        $start->addHours(($region ?? $userRegion)->reset_hours_offset);
        $start->setTimezone($region?->timezone ?? $this->getUserTimezone()); // @phpstan-ignore nullsafe.neverNull

        return $start;
    }
}
