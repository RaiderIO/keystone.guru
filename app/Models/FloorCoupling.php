<?php

namespace App\Models;

use Eloquent;

/**
 * @property int $id
 * @property int $floor1_id
 * @property int $floor2_id
 * @property string $direction
 *
 * @mixin Eloquent
 */
class FloorCoupling extends CacheModel
{
    public const DIRECTION_NONE  = 'none';
    public const DIRECTION_UP    = 'up';
    public const DIRECTION_DOWN  = 'down';
    public const DIRECTION_LEFT  = 'left';
    public const DIRECTION_RIGHT = 'right';

    //
    public $timestamps = false;

    protected $fillable = ['floor1_id', 'floor2_id', 'direction'];
}
