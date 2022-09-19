<?php

namespace App\Models\Patreon;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $key string
 * @property $name string
 *
 * @mixin Eloquent
 * @todo Using CacheModel causes cache problems? People did not get their patreon rewards applied properly because of it?
 */
class PatreonBenefit extends Model
{
    public const AD_FREE                 = 'ad-free';
    public const UNLIMITED_DUNGEONROUTES = 'unlimited-dungeonroutes';
    public const UNLISTED_ROUTES         = 'unlisted-routes';
    public const ANIMATED_POLYLINES      = 'animated-polylines';
    public const ADVANCED_SIMULATION     = 'advanced-simulation';

    public const ALL = [
        self::AD_FREE             => 1,
        //        self::UNLIMITED_DUNGEONROUTES => 2, // This was removed - it's now active for everyone
        self::UNLISTED_ROUTES     => 3,
        self::ANIMATED_POLYLINES  => 4,
        self::ADVANCED_SIMULATION => 5,
    ];

    public $timestamps = false;

    protected $fillable = [
        'id', 'key', 'name',
    ];

    protected $hidden = ['pivot'];

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
