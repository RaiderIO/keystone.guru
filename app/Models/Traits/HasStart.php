<?php


namespace App\Models\Traits;

use App\Models\GameServerRegion;
use App\Traits\UserCurrentTime;
use Illuminate\Support\Carbon;

trait HasStart
{
    use UserCurrentTime;

    /**
     * @param GameServerRegion|null $region
     * @return Carbon The start date of this object.
     */
    public function start(GameServerRegion $region = null): Carbon
    {
        $start      = Carbon::createFromTimeString($this->start, 'UTC');
        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $start->startOfWeek();
        // -1, offset 1 means monday, which we're already at
        $start->addDays(($region ?? $userRegion)->reset_day_offset - 1);
        $start->addHours(($region ?? $userRegion)->reset_hours_offset);
        $start->setTimezone(optional($region)->timezone ?? $this->getUserTimezone());

        return $start;
    }

}
