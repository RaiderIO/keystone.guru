<?php

namespace App\Models\GameVersion;

use App\Models\Expansion;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\SeederModel;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\WagoTools\GameLocale;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Override;

/**
 * @property int      $id
 * @property int      $expansion_id                 The expansion that this game version focussed on.
 * @property int|null $parent_game_version_id       The game version whose dungeons this game version inherits.
 * @property string   $key
 * @property string   $name
 * @property string   $description
 * @property bool     $has_seasons
 * @property bool     $active
 * @property int|null $retired_into_game_version_id The game version this one's content now lives under, when retired.
 *
 * @property Expansion                               $expansion
 * @property GameVersion|null                        $parentGameVersion
 * @property GameVersion|null                        $retiredIntoGameVersion
 * @property EloquentCollection<int, MappingVersion> $mappingVersions
 *
 * @method static Builder<GameVersion> active()
 */
class GameVersion extends Model
{
    use SeederModel;

    protected $hidden = [
        'active',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'id',
        'parent_game_version_id',
        'key',
        'name',
        'description',
        'has_seasons',
        'active',
        'retired_into_game_version_id',
    ];

    protected $with = [
        'expansion',
    ];

    public $timestamps = false;

    public const string DEFAULT_GAME_VERSION = self::GAME_VERSION_RETAIL;

    public const string GAME_VERSION_RETAIL       = 'retail';
    public const string GAME_VERSION_WRATH        = 'wotlk';
    public const string GAME_VERSION_CLASSIC_ERA  = 'classic';
    public const string GAME_VERSION_BETA         = 'beta';
    public const string GAME_VERSION_CATA         = 'cata';
    public const string GAME_VERSION_MOP          = 'mop';
    public const string GAME_VERSION_LEGION_REMIX = 'legion-remix';
    public const string GAME_VERSION_FOREVER      = 'forever';
    public const string GAME_VERSION_TBC          = 'tbc';
    public const string GAME_VERSION_SOD          = 'sod';

    public const array ALL = [
        self::GAME_VERSION_RETAIL       => 1,
        self::GAME_VERSION_CLASSIC_ERA  => 2,
        self::GAME_VERSION_WRATH        => 3,
        self::GAME_VERSION_BETA         => 4,
        self::GAME_VERSION_CATA         => 5,
        self::GAME_VERSION_MOP          => 6,
        self::GAME_VERSION_LEGION_REMIX => 7,
        self::GAME_VERSION_FOREVER      => 8,
        self::GAME_VERSION_TBC          => 9,
        self::GAME_VERSION_SOD          => 10,
    ];

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    #[Override]
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_game_version_id' => 'integer',
        ];
    }

    /**
     * Scope a query to only include active dungeons.
     * @param  Builder<GameVersion> $query
     * @return Builder<GameVersion>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('game_versions.active', 1);
    }

    /**
     * @return BelongsTo<Expansion, $this>
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    /**
     * @return BelongsTo<GameVersion, $this>
     */
    public function parentGameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'parent_game_version_id');
    }

    /**
     * The game version ids whose mapping versions this game version can use, most specific first: this
     * game version's own, then the game version it inherits from.
     *
     * @return array<int, int>
     */
    public function getMappingVersionGameVersionIds(): array
    {
        return $this->parent_game_version_id === null
            ? [$this->id]
            : [$this->id, $this->parent_game_version_id];
    }

    /**
     * Whether this game version lists the mapping version's dungeon: the mapping version is of this game version or
     * of the game version it inherits from.
     */
    public function listsDungeonOfMappingVersion(MappingVersion $mappingVersion): bool
    {
        return in_array($mappingVersion->game_version_id, $this->getMappingVersionGameVersionIds(), true);
    }

    /**
     * Whether routes on the mapping version belong to this game version: it is this game version's own, or the
     * parent's for a dungeon this game version has no mapping version of its own for.
     */
    public function canUseMappingVersion(MappingVersion $mappingVersion): bool
    {
        if ($mappingVersion->game_version_id === $this->id) {
            return true;
        }

        if ($this->parent_game_version_id === null || $mappingVersion->game_version_id !== $this->parent_game_version_id) {
            return false;
        }

        return !MappingVersion::query()
            ->where('dungeon_id', $mappingVersion->dungeon_id)
            ->where('game_version_id', $this->id)
            ->exists();
    }

    /**
     * @return HasMany<MappingVersion, $this>
     */
    public function mappingVersions(): HasMany
    {
        return $this->hasMany(MappingVersion::class);
    }

    /**
     * @return BelongsTo<GameVersion, $this>
     */
    public function retiredIntoGameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'retired_into_game_version_id');
    }

    public function isRetired(): bool
    {
        return $this->retired_into_game_version_id !== null;
    }

    /**
     * @return Collection<int, MappingVersion>
     */
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
     * @return GameVersion The logged-in user's game version, the guest's game version cookie, or the default.
     */
    public static function getUserOrDefaultGameVersion(): GameVersion
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return App::make(GameVersionServiceInterface::class)->getGameVersion(null);
        }

        return self::getUserGameVersion($user) ?? self::getDefaultGameVersion();
    }

    public static function getUserGameVersion(User $user): ?GameVersion
    {
        if ($user->game_version_id <= 0 || $user->gameVersion === null || $user->gameVersion->isRetired()) {
            return null;
        }

        return $user->gameVersion;
    }

    public static function getDefaultGameVersion(): GameVersion
    {
        /** @var CacheServiceInterface $cacheService */
        $cacheService = App::make(CacheServiceInterface::class);

        return $cacheService->remember(
            'default_game_version',
            static fn(
            ) => GameVersion::firstWhere('key', self::DEFAULT_GAME_VERSION),
            config('keystoneguru.cache.default_game_version.ttl'),
        );
    }

    /**
     * The Wowhead sub-domain path for a game version, or null when the game version has its data on
     * retail Wowhead (or is not mapped to a Wowhead database of its own).
     */
    public static function getWowheadDomain(?int $gameVersionId): ?string
    {
        return match ($gameVersionId) {
            self::ALL[self::GAME_VERSION_WRATH]       => 'wrath',
            self::ALL[self::GAME_VERSION_CLASSIC_ERA] => 'classic',
            self::ALL[self::GAME_VERSION_SOD]         => 'classic',
            self::ALL[self::GAME_VERSION_TBC]         => 'tbc',
            self::ALL[self::GAME_VERSION_MOP]         => 'mop-classic',
            default                                   => null,
        };
    }

    /**
     * The Wowhead base URL a link for this game version must be built on - the domain part of a
     * link must match getWowheadDomain()'s tooltip domain for the same game version, or the hover
     * tooltip shows retail data while the link points at a classic database. The locale segment
     * follows the domain (`wowhead.com/classic/de`) and defaults to the application locale.
     */
    public static function getWowheadBaseUrl(?int $gameVersionId, ?string $locale = null): string
    {
        $segments = array_filter([
            'https://www.wowhead.com',
            self::getWowheadDomain($gameVersionId),
            GameLocale::forAppLocale($locale ?? App::getLocale())->wowheadPath(),
        ]);

        return implode('/', $segments);
    }
}
