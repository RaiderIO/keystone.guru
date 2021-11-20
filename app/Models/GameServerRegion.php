<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $short
 * @property string $name
 * @property int $reset_day_offset ISO-8601 numeric representation of the day of the week
 * @property string $reset_hours_offset
 *
 * @property Collection $users
 *
 * @mixin Eloquent
 */
class GameServerRegion extends CacheModel
{
    protected $fillable = ['short', 'name', 'reset_day_offset', 'reset_hours_offset'];
    public $timestamps = false;

    const AMERICAS = 'us';
    const EUROPE   = 'eu';
    const CHINA    = 'cn';
    const TAIWAN   = 'tw';
    const KOREA    = 'kr';

    const DEFAULT_REGION = GameServerRegion::AMERICAS;

    /**
     * @return HasMany
     */
    function users()
    {
        return $this->hasMany('App\User');
    }

    /**
     * @return GameServerRegion Gets the default region.
     */
    public static function getUserOrDefaultRegion(): GameServerRegion
    {
        return optional(Auth::user())->gameserverregion ?? GameServerRegion::where('short', self::DEFAULT_REGION)->first();
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
