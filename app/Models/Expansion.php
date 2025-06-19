<?php

namespace App\Models;

use App\Models\GameVersion\GameVersion;
use App\Models\Timewalking\TimewalkingEvent;
use App\Models\Traits\HasIconFile;
use App\Models\Traits\SeederModel;
use App\Traits\UserCurrentTime;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int                   $id
 * @property int                   $icon_file_id
 * @property bool                  $active
 * @property bool                  $has_wallpaper
 * @property string                $name
 * @property string                $shortname
 * @property string                $color
 *
 * @property Carbon                $released_at
 * @property Carbon                $created_at
 * @property Carbon                $updated_at
 *
 * @property Collection<Dungeon>   $dungeons
 * @property Collection<Dungeon>   $raids
 * @property Collection<Dungeon>   $dungeonsAndRaids
 *
 * @property TimewalkingEvent|null $timewalkingEvent
 *
 * @method static Builder active()
 *
 * @mixin Eloquent
 */
class Expansion extends CacheModel
{
    use HasIconFile;
    use SeederModel;
    use UserCurrentTime;

    public $fillable = ['active', 'icon_file_id', 'name', 'shortname', 'color', 'released_at'];

    public $hidden = ['id', 'icon_file_id', 'created_at', 'updated_at'];

    public $with = ['timewalkingEvent'];

    protected $dates = [
        // 'released_at',
        'created_at',
        'updated_at',
    ];

//    protected $casts = [
//        'released_at' => 'datetime',
//        'created_at'  => 'datetime',
//        'updated_at'  => 'datetime',
//    ];

    public const EXPANSION_CLASSIC      = 'classic';
    public const EXPANSION_TBC          = 'tbc';
    public const EXPANSION_WOTLK        = 'wotlk';
    public const EXPANSION_CATACLYSM    = 'cata';
    public const EXPANSION_MOP          = 'mop';
    public const EXPANSION_WOD          = 'wod';
    public const EXPANSION_LEGION       = 'legion';
    public const EXPANSION_BFA          = 'bfa';
    public const EXPANSION_SHADOWLANDS  = 'sl';
    public const EXPANSION_DRAGONFLIGHT = 'df';
    public const EXPANSION_TWW          = 'tww';
    public const EXPANSION_MIDNIGHT     = 'midnight';
    public const EXPANSION_TLT          = 'tlt';

    public const ALL = [
        self::EXPANSION_CLASSIC      => 'Classic',
        self::EXPANSION_TBC          => 'The Burning Crusade',
        self::EXPANSION_WOTLK        => 'Wrath of the Lich King',
        self::EXPANSION_CATACLYSM    => 'Cataclysm',
        self::EXPANSION_MOP          => 'Mists of Pandaria',
        self::EXPANSION_WOD          => 'Warlords of Draenor',
        self::EXPANSION_LEGION       => 'Legion',
        self::EXPANSION_BFA          => 'Battle for Azeroth',
        self::EXPANSION_SHADOWLANDS  => 'Shadowlands',
        self::EXPANSION_DRAGONFLIGHT => 'Dragonflight',
        self::EXPANSION_TWW          => 'The War Within',
        self::EXPANSION_MIDNIGHT     => 'Midnight',
        self::EXPANSION_TLT          => 'The Last Titan',
    ];

    private ?Collection $currentSeasonCache = null;

    private ?Collection $nextSeasonCache = null;

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    public function getRouteKeyName(): string
    {
        return 'shortname';
    }

    public function dungeons(): HasMany
    {
        return $this->hasMany(Dungeon::class)->where('raid', 0)->orderBy('name');
    }

    public function raids(): HasMany
    {
        return $this->hasMany(Dungeon::class)->where('raid', 1)->orderBy('name');
    }

    public function dungeonsAndRaids(): HasMany
    {
        return $this->hasMany(Dungeon::class)->orderBy('name');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    public function timewalkingEvent(): HasOne
    {
        return $this->hasOne(TimewalkingEvent::class);
    }

    public function currentSeason(?GameServerRegion $gameServerRegion = null): ?Season
    {
        $gameServerRegion ??= GameServerRegion::getUserOrDefaultRegion();

        if ($this->currentSeasonCache === null) {
            $this->currentSeasonCache = collect();
        }

        if ($this->currentSeasonCache->has($gameServerRegion->short)) {
            return $this->currentSeasonCache->get($gameServerRegion->short);
        }

        /** @var Season|null $season */
        $season = $this->hasOne(Season::class)
            ->whereRaw('DATE_ADD(DATE_ADD(`start`, INTERVAL ? day), INTERVAL ? hour) < ?',
                [$gameServerRegion->reset_day_offset, $gameServerRegion->reset_hours_offset, Carbon::now()]
            )
            ->orderBy('start', 'desc')
            ->limit(1)
            ->first();

        $this->currentSeasonCache->put($gameServerRegion->short, $season);

        return $season;
    }

    public function nextSeason(?GameServerRegion $gameServerRegion = null): ?Season
    {
        $gameServerRegion ??= GameServerRegion::getUserOrDefaultRegion();

        if ($this->nextSeasonCache === null) {
            $this->nextSeasonCache = collect();
        }

        if ($this->nextSeasonCache->has($gameServerRegion->short)) {
            return $this->nextSeasonCache->get($gameServerRegion->short);
        }

        /** @var Season|null $season */
        $season = $this->hasOne(Season::class)
            ->where('expansion_id', $this->id)
            ->whereRaw('DATE_ADD(DATE_ADD(`start`, INTERVAL ? day), INTERVAL ? hour) >= ?',
                [$gameServerRegion->reset_day_offset, $gameServerRegion->reset_hours_offset, Carbon::now()]
            )
            ->orderBy('start')
            ->limit(1)
            ->first();

        $this->nextSeasonCache->put($gameServerRegion->short, $season);

        return $season;
    }

    /**
     * Scope a query to only include active dungeons.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expansions.active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('expansions.active', 0);
    }

    public function hasTimewalkingEvent(): bool
    {
        return $this->timewalkingEvent instanceof TimewalkingEvent;
    }

    public function hasRaidForGameVersion(GameVersion $gameVersion): bool
    {
        $result = false;

        foreach ($this->raids as $dungeon) {
            if ($dungeon->game_version_id === $gameVersion->id) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    public function hasDungeonForGameVersion(GameVersion $gameVersion): bool
    {
        $result = false;

        foreach ($this->dungeons as $dungeon) {
            if ($dungeon->game_version_id === $gameVersion->id) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Returns if we should display individual dungeon images
     */
    public function showDiscoverRoutesCardDungeonImage(): bool
    {
        // So far we only have dungeon wallpapers for Shadowlands :(
        return !in_array($this->shortname, [Expansion::EXPANSION_SHADOWLANDS]);
    }

    public function getWallpaperUrl(): string
    {
        return ksgAssetImage(sprintf('dungeons/%s/wallpaper.jpg', $this->shortname));
    }

    /**
     * Saves an expansion with the data from a Request.
     *
     *
     * @throws Exception
     */
    public function saveFromRequest(Request $request, string $fileUploadDirectory = 'uploads'): bool
    {
        $new = isset($this->id);

        $file = $request->file('icon');

        $this->icon_file_id = -1;
        $this->active       = $request->get('active');
        $this->name         = $request->get('name');
        $this->shortname    = $request->get('shortname');
        $this->color        = $request->get('color');

        // Update or insert it
        if ($this->save()) {
            // Save was successful, now do any file handling that may be necessary
            if ($file !== null) {
                try {
                    $icon = File::saveFileToDB($file, $this, $fileUploadDirectory, 'local_public');

                    // Update the expansion to reflect the new file ID
                    $this->icon_file_id = $icon->id;
                    $this->save();
                } catch (Exception $ex) {
                    if ($new) {
                        // Roll back the saving of the expansion since something went wrong with the file.
                        $this->delete();
                    }

                    throw $ex;
                }
            }

            return true;
        }

        return false;
    }
}
