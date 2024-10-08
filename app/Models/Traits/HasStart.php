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

        $start->startOfWeek();
        $start->addDays(($region ?? $userRegion)->reset_day_offset);
        $start->addHours(($region ?? $userRegion)->reset_hours_offset);
        $start->setTimezone($region?->timezone ?? $this->getUserTimezone());

        return $start;
    }
}
