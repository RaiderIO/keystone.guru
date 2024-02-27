<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;

/**
 * @property int $id
 * @property string $name
 *
 * @mixin Eloquent
 */
class RaidMarker extends CacheModel
{
    use SeederModel;

    public const RAID_MARKER_STAR = 'star';

    public const RAID_MARKER_CIRCLE = 'circle';

    public const RAID_MARKER_DIAMOND = 'diamond';

    public const RAID_MARKER_TRIANGLE = 'triangle';

    public const RAID_MARKER_MOON = 'moon';

    public const RAID_MARKER_SQUARE = 'square';

    public const RAID_MARKER_CROSS = 'cross';

    public const RAID_MARKER_SKULL = 'skull';

    public const ALL = [
        self::RAID_MARKER_STAR => 1,
        self::RAID_MARKER_CIRCLE => 2,
        self::RAID_MARKER_DIAMOND => 3,
        self::RAID_MARKER_TRIANGLE => 4,
        self::RAID_MARKER_MOON => 5,
        self::RAID_MARKER_SQUARE => 6,
        self::RAID_MARKER_CROSS => 7,
        self::RAID_MARKER_SKULL => 8,
    ];

    public $timestamps = false;

    protected $fillable = ['id', 'name'];
}
