<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $category
 * @property string $key
 * @property string $name
 *
 * @mixin Eloquent
 */
class RouteAttribute extends Model
{
    public const ROUTE_ATTRIBUTE_ROGUE_SHROUD_SKIP        = 'rogue_shroud_skip';
    public const ROUTE_ATTRIBUTE_WARLOCK_GATE_SKIP        = 'warlock_gate_skip';
    public const ROUTE_ATTRIBUTE_MAGE_SLOW_FALL_SKIP      = 'mage_slow_fall_skip';
    public const ROUTE_ATTRIBUTE_INVISIBILITY_POTION_SKIP = 'invisibility_potion';
    public const ROUTE_ATTRIBUTE_DEATH_SKIP               = 'death_skip';

    public const ALL = [
        self::ROUTE_ATTRIBUTE_ROGUE_SHROUD_SKIP,
        self::ROUTE_ATTRIBUTE_WARLOCK_GATE_SKIP,
        self::ROUTE_ATTRIBUTE_MAGE_SLOW_FALL_SKIP,
        self::ROUTE_ATTRIBUTE_INVISIBILITY_POTION_SKIP,
        self::ROUTE_ATTRIBUTE_DEATH_SKIP,
    ];

    public $timestamps = false;

    public $hidden = ['id', 'pivot'];

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
