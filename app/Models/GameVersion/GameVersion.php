<?php

namespace App\Models\GameVersion;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * @property int    $id
 * @property string $key
 * @property string $name
 * @property string $description
 * @property bool   $has_seasons
 * @property bool   $active
 *
 * @method static Builder active()
 */
class GameVersion extends CacheModel
{
    use SeederModel;

    protected $fillable = [
        'id',
        'key',
        'name',
        'description',
        'has_seasons',
        'active',
    ];

    public $timestamps = false;

    public const DEFAULT_GAME_VERSION = self::GAME_VERSION_RETAIL;

    public const GAME_VERSION_RETAIL      = 'retail';
    public const GAME_VERSION_WRATH       = 'wotlk';
    public const GAME_VERSION_CLASSIC_ERA = 'classic';
    public const GAME_VERSION_BETA        = 'beta';

    public const ALL = [
        self::GAME_VERSION_RETAIL      => 1,
        self::GAME_VERSION_CLASSIC_ERA => 2,
        self::GAME_VERSION_WRATH       => 3,
        self::GAME_VERSION_BETA        => 4,
    ];

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * Scope a query to only include active dungeons.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('game_versions.active', 1);
    }

    /**
     * @return GameVersion Gets the default game version.
     */
    public static function getUserOrDefaultGameVersion(): GameVersion
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user->game_version_id > 0 && $user->gameVersion !== null) {
                return $user->gameVersion;
            }
        }

        /** @var CacheServiceInterface $cacheService */
        $cacheService = App::make(CacheServiceInterface::class);

        return $cacheService->remember('default_game_version', static fn() => GameVersion::where('key', self::DEFAULT_GAME_VERSION)->first());
    }
}
