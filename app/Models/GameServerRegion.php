<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use App\Service\Cache\CacheServiceInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * @property int        $id
 * @property string     $short
 * @property string     $name
 * @property string     $timezone
 * @property int        $reset_day_offset ISO-8601 numeric representation of the day of the week
 * @property string     $reset_hours_offset
 * @property Collection $users
 *
 * @mixin Eloquent
 */
class GameServerRegion extends CacheModel
{
    use SeederModel;

    protected $fillable = ['short', 'name', 'timezone', 'reset_day_offset', 'reset_hours_offset'];

    public $timestamps = false;

    public const AMERICAS = 'us';
    public const EUROPE   = 'eu';
    public const CHINA    = 'cn';
    public const TAIWAN   = 'tw';
    public const KOREA    = 'kr';

    public const DEFAULT_REGION = GameServerRegion::AMERICAS;

    public const ALL = [
        self::AMERICAS => 1,
        self::EUROPE   => 2,
        self::CHINA    => 3,
        self::TAIWAN   => 4,
        self::KOREA    => 5,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return GameServerRegion Gets the default region.
     */
    public static function getUserOrDefaultRegion(): GameServerRegion
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->game_server_region_id > 0 && $user->gameServerRegion !== null) {
                return $user->gameServerRegion;
            }
        }

        /** @var CacheServiceInterface $cacheService */
        $cacheService = App::make(CacheServiceInterface::class);

        return $cacheService->remember('default_region',
            static fn() => GameServerRegion::where('short', self::DEFAULT_REGION)->first()
        );
    }
}
