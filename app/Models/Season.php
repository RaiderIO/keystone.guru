<?php

namespace App\Models;

use App\Service\Season\SeasonService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @property $id int
 * @property $seasonal_affix_id int
 * @property $start datetime
 * @property $presets int
 *
 * @property Collection $affixgroups
 *
 * @mixin \Eloquent
 */
class Season extends Model
{
    public $with = ['affixgroups'];
    public $timestamps = false;

    /**
     * @return \Illuminate\Config\Repository|mixed|string
     */
    private function _getUserTimezone()
    {
        // Find the timezone that makes the most sense
        $timezone = config('app.timezone');

        // But if logged in, get the user's timezone instead
        if (Auth::check()) {
            $timezone = Auth::user()->timezone;
        }
        return $timezone;
    }

    /**
     * @return Carbon Get a date of now with the timezone set properly.
     * @todo This is a copy of the service function
     */
    private function _getNow()
    {
        return Carbon::now($this->_getUserTimezone());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function affixgroups()
    {
        return $this->hasMany('App\Models\AffixGroup');
    }

    /**
     * @return Carbon The start date of this season.
     */
    public function start()
    {
        $start = Carbon::createFromTimeString($this->start, 'UTC');

        $region = GameServerRegion::getUserOrDefaultRegion();
        $start->startOfWeek();
        // -1, offset 1 means monday, which we're already at
        $start->addDays($region->reset_day_offset - 1);
        $start->addHours($region->reset_hours_offset);
        $start->setTimezone($this->_getUserTimezone());

        return $start;
    }

    /**
     * Get the amount of weeks that have passed since the start of the M+ season, on a specific date.
     * @param Carbon $date
     * @return int
     */
    public function getWeeksSinceStartAt($date)
    {
        $start = $this->start();

        // Target date
        $targetTime = Carbon::create($date->year, $date->month, $date->day, $date->hour, null, null, $date->timezone);

        // Get the week difference
        return $start->diffInWeeks($targetTime);
    }

    /**
     * Get the amount of full iterations of the entire list of affix groups that this season has done, since the start
     * of the season.
     */
    public function getAffixGroupIterations()
    {
        return $this->getAffixGroupIterationsAt($this->_getNow());
    }

    /**
     * Get the amount of full iterations of the entire list of affix groups
     *
     * @param $date
     * @return int
     */
    public function getAffixGroupIterationsAt($date)
    {
        $weeksSinceStart = $this->getWeeksSinceStartAt($date);

        // Round down
        return (int)($weeksSinceStart / $this->affixgroups->count());
    }

    /**
     * Get the affix group that is currently active in the user's timezone (if user timezone was set).
     *
     * @return AffixGroup
     */
    public function getCurrentAffixGroup()
    {
        $result = false;
        try {
            $result = $this->getAffixGroupAtTime($this->_getNow());
        } catch (\Exception $ex) {
            Log::error('Error getting current affix group: ' . $ex->getMessage());
        }
        return $result;
    }


    /**
     * Get which affix group is active on this region at a specific point in time.
     *
     * @param Carbon $date The date at which you want to know the affix group.
     * @return AffixGroup The affix group that is active at that point in time for your passed timezone.
     * @throws \Exception
     */
    public function getAffixGroupAtTime($date)
    {
        /** @var SeasonService $seasonService */
        $start = $this->start();
        if ($date->lt($start)) {
            throw new \Exception('Cannot find an affix group of this season before it\'s started!');
        }

        // Service injection, we do not know ourselves the total iterations done. Our history starts at a date,
        // we do not know anything before that so we need help
        $seasonService = resolve(SeasonService::class);

        // Get the affix group which occurs after a few weeks and return that
        return $this->affixgroups[$seasonService->getAffixGroupIndexAt($date)];
    }

    /**
     * Get the current preset (if any) at a specific date.
     * @param Carbon $date
     * @return int The preset at the passed date.
     */
    public function getPresetAt(Carbon $date)
    {
        return $this->getWeeksSinceStartAt($date) % $this->presets + 1;
    }
}
