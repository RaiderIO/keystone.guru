<?php

namespace App\Models\GameVersion;

use App\Models\CacheModel;

/**
 * @property int    $id
 * @property string $key
 * @property string $description
 */
class GameVersion extends CacheModel
{
    protected $fillable   = [
        'key',
        'description'
    ];
    public    $timestamps = false;

    public const GAME_VERSION_RETAIL  = 1;
    public const GAME_VERSION_WOTLK   = 2;
    public const GAME_VERSION_CLASSIC = 3;

    public const ALL = [
        self::GAME_VERSION_RETAIL  => 1,
        self::GAME_VERSION_WOTLK   => 2,
        self::GAME_VERSION_CLASSIC => 3,
    ];
}
