<?php

namespace App\Models\GameVersion;

use App\Models\CacheModel;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * @property int    $id
 * @property string $key
 * @property string $name
 * @property string $description
 */
class GameVersion extends CacheModel
{
    protected $fillable   = [
        'id',
        'key',
        'name',
        'description',
    ];
    public    $timestamps = false;

    private const DEFAULT_GAME_VERSION = self::GAME_VERSION_RETAIL;

    public const GAME_VERSION_RETAIL  = 'retail';
    public const GAME_VERSION_WOTLK   = 'wotlk';
    public const GAME_VERSION_CLASSIC = 'classic';

    public const ALL = [
        self::GAME_VERSION_RETAIL  => 1,
        self::GAME_VERSION_WOTLK   => 2,
        self::GAME_VERSION_CLASSIC => 3,
    ];


    /**
     * @return GameVersion Gets the default game version.
     */
    public static function getUserOrDefaultGameVersion(): GameVersion
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->game_version_id > 0 && $user->gameVersion !== null) {
                return $user->gameVersion;
            }
        }

        /** @var CacheServiceInterface $cacheService */
        $cacheService = App::make(CacheServiceInterface::class);

        return $cacheService->remember('default_game_version', function () {
            return GameVersion::where('key', self::DEFAULT_GAME_VERSION)->first();
        });
    }
}
