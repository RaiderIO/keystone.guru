<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property \Illuminate\Support\Collection $specializations
 */
class GameServerRegion extends Model
{
    protected $fillable = ['short', 'name'];
    public $timestamps = false;

    /**
     * @return string The key as used in the front-end to identify the dungeon.
     */
    public function getKeyAttribute()
    {
        return strtolower(str_replace(" ", "", $this->name));
    }

    function getThisWeeksAffixGroup()
    {

        $currentWeek = intval(date('W'));
        $currentDay = intval(date('w'));


        $firstWeek = config('keystoneguru.season_start_week') + $affixGroupIndex;
        $firstWeekTime = strtotime('2018W' . $firstWeek) + (24 * 3600);
        $nextWeek = config('keystoneguru.season_start_week') + $affixGroupIndex + 1;
        $nextWeekTime = strtotime('2018W' . $nextWeek) + (24 * 3600);
        $currentWeekTime = strtotime('2018W' . $currentWeek) + ((24 * 3600) * $currentDay);
        $currentWeekClass = $currentWeekTime > $firstWeekTime && $currentWeekTime <= $nextWeekTime ? 'current_week ' : '';
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
