<?php

namespace App\Models\GameVersion;

use App\Models\CacheModel;
use App\Models\Expansion;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\SeederModel;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * @property int    $id
 * @property int    $expansion_id The expansion that this game version focussed on.
 * @property string $key
 * @property string $name
 * @property string $description
 * @property bool   $has_seasons
 * @property bool   $active
 *
 * @property Expansion                  $expansion
 * @property Collection<MappingVersion> $mappingVersions
 *
 * @method static Builder active()
 */
class GameVersion extends CacheModel
{
    use SeederModel;

    protected $hidden = [
        'active',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'id',
        'key',
        'name',
        'description',
        'has_seasons',
        'active',
    ];

    protected $with = [
        'expansion',
    ];

    public $timestamps = false;

    public const DEFAULT_GAME_VERSION = self::GAME_VERSION_RETAIL;

    public const GAME_VERSION_RETAIL       = 'retail';
    public const GAME_VERSION_WRATH        = 'wotlk';
    public const GAME_VERSION_CLASSIC_ERA  = 'classic';
    public const GAME_VERSION_BETA         = 'beta';
    public const GAME_VERSION_CATA         = 'cata';
    public const GAME_VERSION_MOP          = 'mop';
    public const GAME_VERSION_LEGION_REMIX = 'legion-remix';

    public const ALL = [
        self::GAME_VERSION_RETAIL       => 1,
        self::GAME_VERSION_CLASSIC_ERA  => 2,
        self::GAME_VERSION_WRATH        => 3,
        self::GAME_VERSION_BETA         => 4,
        self::GAME_VERSION_CATA         => 5,
        self::GAME_VERSION_MOP          => 6,
        self::GAME_VERSION_LEGION_REMIX => 7,
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

    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    public function mappingVersions(): HasMany
    {
        return $this->hasMany(MappingVersion::class);
    }

    public function getDungeonsWithHeatmapsEnabled(): Collection
    {
        return $this->mappingVersions->filter(fn(
            MappingVersion $mappingVersion,
        ) => $mappingVersion->dungeon !== null && $mappingVersion->dungeon->heatmap_enabled);
    }

    /**
     * Returns if we should display individual dungeon images
     */
    public function showDiscoverRoutesCardDungeonImage(): bool
    {
        return !in_array($this->expansion->shortname, [
            Expansion::EXPANSION_MOP,
            Expansion::EXPANSION_SHADOWLANDS,
            Expansion::EXPANSION_TWW,
        ]);
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

        return self::getDefaultGameVersion();
    }

    public static function getDefaultGameVersion(): GameVersion
    {
        /** @var CacheServiceInterface $cacheService */
        $cacheService = App::make(CacheServiceInterface::class);

        return $cacheService->remember('default_game_version', static fn(
        ) => GameVersion::firstWhere('key', self::DEFAULT_GAME_VERSION),
            config('keystoneguru.cache.default_game_version.ttl'),
        );
    }
}
