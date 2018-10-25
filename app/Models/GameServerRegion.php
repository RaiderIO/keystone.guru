<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
     * Get the affix group that is currently active on this region.
     *
     * @return AffixGroup
     */
    function getCurrentAffixGroup()
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

        return $this->getAffixGroupAtTime($currentYear, $currentMonth, $currentDay, $currentHour, $timezone);
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
        // Setup
        $startWeek = config('keystoneguru.season_start_week');
        $startYear = config('keystoneguru.season_start_year');
        $daySeconds = (24 * 3600);
        $affixGroups = AffixGroup::all();

        // Get the time the season started for this region, correct reset_day_offset since it's 1-based rather than 0-based.
        $regionFirstWeekTime = Carbon::createFromTimestamp(
            strtotime($startYear . 'W' . $startWeek . ' ' . $this->reset_time_offset_utc) +
            (($this->reset_day_offset - 1) * $daySeconds),
            // reset_time_offset_utc is in UTC (doh) so this should be UTC too
            'UTC'
        );

        // Target date
        $targetTime = Carbon::create($year, $month, $day, $hour, null, null, $timezone);

        // Get the week difference
        $weeksPassed = $regionFirstWeekTime->diffInWeeks($targetTime);

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

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
