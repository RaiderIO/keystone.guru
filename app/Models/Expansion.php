<?php

namespace App\Models;

use App\Models\Timewalking\TimewalkingEvent;
use App\Models\Traits\HasIconFile;
use Carbon\Carbon;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $icon_file_id
 * @property int $active
 * @property string $name
 * @property string $shortname
 * @property string $color
 *
 * @property Carbon $released_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|Dungeon[] $dungeons
 * @property TimewalkingEvent|null $timewalkingevent
 * @property Season|null $currentseason
 * @property Season|null $nextseason
 *
 * @mixin Eloquent
 */
class Expansion extends CacheModel
{
    use HasIconFile;

    public $fillable = ['active', 'icon_file_id', 'name', 'shortname', 'color', 'released_at'];
    public $hidden = ['id', 'icon_file_id', 'created_at', 'updated_at'];
    public $with = ['timewalkingevent'];

    protected $dates = [
        // 'released_at',
        'created_at',
        'updated_at',
    ];

    const EXPANSION_VANILLA      = 'vanilla';
    const EXPANSION_TBC          = 'tbc';
    const EXPANSION_WOTLK        = 'wotlk';
    const EXPANSION_CATACLYSM    = 'cata';
    const EXPANSION_MOP          = 'mop';
    const EXPANSION_WOD          = 'wod';
    const EXPANSION_LEGION       = 'legion';
    const EXPANSION_BFA          = 'bfa';
    const EXPANSION_SHADOWLANDS  = 'shadowlands';
    const EXPANSION_DRAGONFLIGHT = 'dragonflight';

    const ALL = [
        'Classic'                => self::EXPANSION_VANILLA,
        'The Burning Crusade'    => self::EXPANSION_TBC,
        'Wrath of the Lich King' => self::EXPANSION_WOTLK,
        'Cataclysm'              => self::EXPANSION_CATACLYSM,
        'Mists of Pandaria'      => self::EXPANSION_MOP,
        'Warlords of Draenor'    => self::EXPANSION_WOD,
        'Legion'                 => self::EXPANSION_LEGION,
        'Battle for Azeroth'     => self::EXPANSION_BFA,
        'Shadowlands'            => self::EXPANSION_SHADOWLANDS,
        'Dragonflight'           => self::EXPANSION_DRAGONFLIGHT,
    ];

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'shortname';
    }

    /**
     * @return HasMany
     */
    public function dungeons(): HasMany
    {
        return $this->hasMany('App\Models\Dungeon');
    }

    /**
     * @return HasMany
     */
    public function seasons(): HasMany
    {
        return $this->hasMany('App\Models\Season');
    }

    /**
     * @return HasOne
     */
    public function timewalkingevent(): HasOne
    {
        return $this->hasOne('App\Models\Timewalking\TimewalkingEvent');
    }

    /**
     * @return HasOne
     */
    public function currentseason(): HasOne
    {
        return $this->hasOne(Season::class)
            ->where('expansion_id', $this->id)
            ->where('start', '<', Carbon::now()->toDateString())
            ->orderBy('start', 'desc')
            ->limit(1);
    }

    /**
     * @return HasOne
     */
    public function nextseason(): HasOne
    {
        return $this->hasOne(Season::class)
            ->where('expansion_id', $this->id)
            ->where('start', '>', Carbon::now()->toDateString())
            ->orderBy('start')
            ->limit(1);
    }

    /**
     * Scope a query to only include active dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expansions.active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('expansions.active', 0);
    }

    /**
     * @return bool
     */
    public function hasTimewalkingEvent(): bool
    {
        return $this->timewalkingevent instanceof TimewalkingEvent;
    }

    /**
     * Saves an expansion with the data from a Request.
     *
     * @param Request $request
     * @param string $fileUploadDirectory
     * @return bool
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
                    $icon = File::saveFileToDB($file, $this, $fileUploadDirectory);

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
