<?php

namespace App\Models;

use Eloquent;

/**
 * @property $id int
 * @property $key string
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
        self::AD_FREE                 => 1,
        self::UNLIMITED_DUNGEONROUTES => 2,
        self::UNLISTED_ROUTES         => 3,
        self::ANIMATED_POLYLINES      => 4,
    ];

    public $timestamps = false;

    protected $fillable = [
        'id', 'name',
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
