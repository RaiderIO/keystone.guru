<?php

namespace App\Models\Floor;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
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
    use SeederModel;

    public const DIRECTION_NONE = 'none';

    public const DIRECTION_UP = 'up';

    public const DIRECTION_DOWN = 'down';

    public const DIRECTION_LEFT = 'left';

    public const DIRECTION_RIGHT = 'right';

    public const ALL = [
        self::DIRECTION_NONE,
        self::DIRECTION_UP,
        self::DIRECTION_DOWN,
        self::DIRECTION_LEFT,
        self::DIRECTION_RIGHT,
    ];

    //
    public $timestamps = false;

    protected $fillable = ['floor1_id', 'floor2_id', 'direction'];
}
