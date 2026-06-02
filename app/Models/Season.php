<?php

namespace App\Models;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Traits\HasStart;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int    $id
 * @property int    $expansion_id
 * @property int    $seasonal_affix_id
 * @property int    $index
 * @property Carbon $start
 * @property int    $presets
 * @property int    $affix_group_count
 * @property int    $start_affix_group_index The index of the affix that was the first affix to be available upon season start
 * @property int    $key_level_min
 * @property int    $key_level_max
 * @property int    $item_level_min          The minimum item level of items that can be obtained in this season
 * @property int    $item_level_max          The maximum item level of items that can be obtained in this season
 * @property string $name                    Dynamic attribute
 * @property string $name_med                Dynamic attribute
 * @property string $name_long               Dynamic attribute
 * @property int    $start_period            Dynamic attribute
 *
 * @property Expansion $expansion
 *
 * @property Collection<AffixGroup>    $affixGroups
 * @property Collection<Dungeon>       $dungeons
 * @property Collection<SeasonDungeon> $seasonDungeons
 *
 * @mixin Eloquent
 */
class Season extends CacheModel
{
    use HasStart;
    use SeederModel;

    const int SEASON_BFA_S1       = 1;
    const int SEASON_BFA_S2       = 2;
    const int SEASON_BFA_S3       = 3;
    const int SEASON_BFA_S4       = 4;
    const int SEASON_SL_S1        = 5;
    const int SEASON_SL_S2        = 6;
    const int SEASON_LEGION_TW_S1 = 7;
    const int SEASON_SL_S3        = 8;
    const int SEASON_SL_S4        = 9;
    const int SEASON_DF_S1        = 10;
    const int SEASON_DF_S2        = 11;
    const int SEASON_DF_S3        = 12;
    const int SEASON_DF_S4        = 13;
    const int SEASON_TWW_S1       = 14;
    const int SEASON_TWW_S2       = 15;
    const int SEASON_TWW_S3       = 16;
    const int SEASON_MIDNIGHT_S1  = 17;

    const array ALL_SEASONS = [
        self::SEASON_BFA_S1,
        self::SEASON_BFA_S2,
        self::SEASON_BFA_S3,
        self::SEASON_BFA_S4,
        self::SEASON_SL_S1,
        self::SEASON_SL_S2,
        self::SEASON_LEGION_TW_S1,
        self::SEASON_SL_S3,
        self::SEASON_SL_S4,
        self::SEASON_DF_S1,
        self::SEASON_DF_S2,
        self::SEASON_DF_S3,
        self::SEASON_DF_S4,
        self::SEASON_TWW_S1,
        self::SEASON_TWW_S2,
        self::SEASON_TWW_S3,
        self::SEASON_MIDNIGHT_S1,
    ];

    protected $fillable = [
        'expansion_id',
        'seasonal_affix_id',
        'index',
        'start',
        'presets',
        'affix_group_count',
        'start_affix_group_index',
        'key_level_min',
        'key_level_max',
        'item_level_min',
        'item_level_max',
    ];

    public $with = [
        'expansion',
        'affixGroups',
        'dungeons',
    ];

    public $timestamps = false;

    protected $appends = [
        'name',
        'name_long',
        'start_period',
    ];

    protected function casts(): array
    {
        return [
            'start'          => 'datetime',
            'key_level_min'  => 'integer',
            'key_level_max'  => 'integer',
            'item_level_min' => 'integer',
            'item_level_max' => 'integer',
        ];
    }

    public function getNameAttribute(): string
    {
        return __('seasons.name', ['season' => $this->index]);
    }

    public function getNameLongAttribute(): string
    {
        return __('seasons.name_long', [
            'expansion' => __($this->expansion->name),
            'season'    => $this->index,
        ]);
    }

    public function getStartPeriodAttribute(): int
    {
        return GameServerRegion::getUserOrDefaultRegion()->getKeystoneLeaderboardPeriod($this->start);
    }

    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    public function affixGroups(): HasMany
    {
        return $this->hasMany(AffixGroup::class);
    }

    /** @return BelongsToMany<Dungeon, Season> */
    public function dungeons(): BelongsToMany
    {
        return $this->belongsToMany(Dungeon::class, 'season_dungeons')->orderBy('season_dungeons.id');
    }

    public function seasonDungeons(): HasMany
    {
        return $this->hasMany(SeasonDungeon::class);
    }

    public function hasDungeon(Dungeon $dungeon): bool
    {
        return $this->seasonDungeons()->where('dungeon_id', $dungeon->id)->exists();
    }
}
