<?php

namespace App\Models;

use Eloquent;

/**
 * @property $id int
 * @property $name string
 *
 * @mixin Eloquent
 */
class PaidTier extends CacheModel
{
    public const AD_FREE                 = 'ad-free';
    public const UNLIMITED_DUNGEONROUTES = 'unlimited-dungeonroutes';
    public const UNLISTED_ROUTES         = 'unlisted-routes';
    public const ANIMATED_POLYLINES      = 'animated-polylines';

    public const ALL = [
        self::AD_FREE,
        self::UNLIMITED_DUNGEONROUTES,
        self::UNLISTED_ROUTES,
        self::ANIMATED_POLYLINES,
    ];

    public $timestamps = false;

    protected $fillable = [
        'name',
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
