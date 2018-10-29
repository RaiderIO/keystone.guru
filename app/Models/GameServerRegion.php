<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $short
 * @property string $name
 * @property int $reset_day_offset ISO-8601 numeric representation of the day of the week
 * @property string $reset_time_offset_utc
 * @property \Illuminate\Support\Collection $specializations
 */
class GameServerRegion extends Model
{
    protected $fillable = ['short', 'name', 'reset_day_offset', 'reset_time_offset_utc'];
    public $timestamps = false;

    /**
     * @var null Cached list of affix groups to help speed up calls
     */
    private $_affixGroups = null;

    /**
     * Gets a list of all affix groups (may be cached).
     *
     * @return AffixGroup[]|\Illuminate\Database\Eloquent\Collection|null
     */
    function _getAllAffixGroups()
    {
        if ($this->_affixGroups === null) {
            $this->_affixGroups = AffixGroup::all();
        }
        return $this->_affixGroups;
    }

    /**
     * @return array Helper function for getting some stats related to today.
     */
    function _getNow()
    {
        // Gather some data
        $currentYear = intval(date('Y'));
        $currentMonth = intval(date('m'));
        $currentDay = intval(date('d'));
        $currentHour = intval(date('H'));

        // Find the timezone that makes the most sense
        $timezone = config('app.timezone');

        // But if logged in, get the user's timezone instead
        if (Auth::check()) {
            $timezone = Auth::user()->timezone;
        }

        return [
            'year' => $currentYear,
            'month' => $currentMonth,
            'day' => $currentDay,
            'hour' => $currentHour,
            'timezone' => $timezone
        ];
    }

    /**
     * @return Carbon Get a Carbon date object with the date of the current Season's start.
     */
    function _getSeasonStart()
    {
        // Setup
        $startWeek = config('keystoneguru.season_start_week');
        $startYear = config('keystoneguru.season_start_year');
        $daySeconds = (24 * 3600);

        return Carbon::createFromTimestamp(
            strtotime($startYear . 'W' . $startWeek . ' ' . $this->reset_time_offset_utc) +
            (($this->reset_day_offset - 1) * $daySeconds),
            // reset_time_offset_utc is in UTC (doh) so this should be UTC too
            'UTC'
        );
    }

    /**
     * Get the amount of weeks that have passed since the start of the M+ season, on a specific date
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param string $timezone
     * @return int
     */
    function _getWeeksPassedSinceStart($year, $month, $day, $hour, $timezone = null)
    {
        // Get the time the season started for this region, correct reset_day_offset since it's 1-based rather than 0-based.
        $regionFirstWeekTime = $this->_getSeasonStart();

        // Target date
        $targetTime = Carbon::create($year, $month, $day, $hour, null, null, $timezone);

        // Get the week difference
        return $regionFirstWeekTime->diffInWeeks($targetTime);
    }

    /**
     * @return int Get the amount of full iterations over all affix groups we've done this season.
     */
    function getCurrentSeasonAffixGroupIteration()
    {
        $now = $this->_getNow();

        $affixGroups = $this->_getAllAffixGroups();

        $weeksPassed = $this->_getWeeksPassedSinceStart($now['year'], $now['month'], $now['day'], $now['hour'], $now['timezone']);

        // Using the week difference, find the current affix
        return (int)($weeksPassed / $affixGroups->count());
    }

    /**
     * Get the start date of an affix group based on the amount of iterations there's been on the calendar.
     *
     * @param $iteration
     * @param $affixGroup
     * @return Carbon
     */
    function getAffixGroupStartDate($iteration, $affixGroup)
    {
        /** @var Collection $affixGroups */
        $affixGroups = $this->_getAllAffixGroups();

        $index = 0;
        for ($i = 0; $i < $affixGroups->count(); $i++) {
            if ($affixGroups->get($i)->id === $affixGroup->id) {
                $index = $i;
                break;
            }
        }

        $now = $this->_getNow();

        $weeksPassed = ($iteration * $affixGroups->count()) + $index;
        return $this->_getSeasonStart()->setTimezone($now['timezone'])->addWeeks($weeksPassed);
    }

    /**
     * Get the affix group that is currently active in the user's timezone (if user timezone was set).
     *
     * @return AffixGroup
     */
    function getCurrentAffixGroup()
    {
        $now = $this->_getNow();

        return $this->getAffixGroupAtTime($now['year'], $now['month'], $now['day'], $now['hour'], $now['timezone']);
    }

    /**
     * Get which affix group is active on this region at a specific point in time.
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param string $timezone
     * @return AffixGroup The affix group that is active at that point in time for your passed timezone.
     */
    function getAffixGroupAtTime($year, $month, $day, $hour, $timezone = null)
    {
        $affixGroups = $this->_getAllAffixGroups();

        $weeksPassed = $this->_getWeeksPassedSinceStart($year, $month, $day, $hour, $timezone);

        // Using the week difference, find the current affix
        $index = $weeksPassed % $affixGroups->count();

        // Return the affix for that timestamp
        return $affixGroups->get($index);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function users()
    {
        return $this->hasMany('App\User');
    }

    /**
     * @return mixed Gets the default region.
     */
    public static function getUserOrDefaultRegion()
    {
        $region = null;
        if (Auth::check()) {
            $region = Auth::user()->gameserverregion;
        }
        if ($region === null) {
            $region = GameServerRegion::all()->where('short', 'na')->first();
        }
        return $region;
    }

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
