<?php

namespace App\Models;

use Eloquent;

/**
 * @property int $id
 * @property string $name
 *
 * @mixin Eloquent
 */
class RaidMarker extends CacheModel
{
    const RAID_MARKER_STAR     = 'star';
    const RAID_MARKER_CIRCLE   = 'circle';
    const RAID_MARKER_DIAMOND  = 'diamond';
    const RAID_MARKER_TRIANGLE = 'triangle';
    const RAID_MARKER_MOON     = 'moon';
    const RAID_MARKER_SQUARE   = 'square';
    const RAID_MARKER_CROSS    = 'cross';
    const RAID_MARKER_SKULL    = 'skull';

    const ALL = [
        self::RAID_MARKER_STAR     => 1,
        self::RAID_MARKER_CIRCLE   => 2,
        self::RAID_MARKER_DIAMOND  => 3,
        self::RAID_MARKER_TRIANGLE => 4,
        self::RAID_MARKER_MOON     => 5,
        self::RAID_MARKER_SQUARE   => 6,
        self::RAID_MARKER_CROSS    => 7,
        self::RAID_MARKER_SKULL    => 8,
    ];

    public $timestamps = false;

    protected $fillable = ['id', 'name'];

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
