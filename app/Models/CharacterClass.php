<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $color
 *
 * @property Collection|CharacterClassSpecialization[] $specializations
 * @property Collection|DungeonRoutePlayerClass[] $dungeonrouteplayerclasses
 * @property Collection|DungeonRoutePlayerRace[] $dungeonrouteplayerraces
 *
 * @mixin Eloquent
 */
class CharacterClass extends CacheModel
{
    use HasIconFile;

    public $timestamps = false;
    public $hidden = ['icon_file_id', 'pivot'];
    public $fillable = ['key', 'name', 'color'];

    public const CHARACTER_CLASS_WARRIOR      = 'warrior';
    public const CHARACTER_CLASS_HUNTER       = 'hunter';
    public const CHARACTER_CLASS_DEATH_KNIGHT = 'deathknight';
    public const CHARACTER_CLASS_MAGE         = 'mage';
    public const CHARACTER_CLASS_PRIEST       = 'priest';
    public const CHARACTER_CLASS_MONK         = 'monk';
    public const CHARACTER_CLASS_ROGUE        = 'rogue';
    public const CHARACTER_CLASS_WARLOCK      = 'warlock';
    public const CHARACTER_CLASS_SHAMAN       = 'shaman';
    public const CHARACTER_CLASS_PALADIN      = 'paladin';
    public const CHARACTER_CLASS_DRUID        = 'druid';
    public const CHARACTER_CLASS_DEMON_HUNTER = 'demonhunter';
    public const CHARACTER_CLASS_EVOKER       = 'evoker';

    // Do NOT change the order of this array!
    public const ALL = [
        self::CHARACTER_CLASS_WARRIOR,
        self::CHARACTER_CLASS_HUNTER,
        self::CHARACTER_CLASS_DEATH_KNIGHT,
        self::CHARACTER_CLASS_MAGE,
        self::CHARACTER_CLASS_PRIEST,
        self::CHARACTER_CLASS_MONK,
        self::CHARACTER_CLASS_ROGUE,
        self::CHARACTER_CLASS_WARLOCK,
        self::CHARACTER_CLASS_SHAMAN,
        self::CHARACTER_CLASS_PALADIN,
        self::CHARACTER_CLASS_DRUID,
        self::CHARACTER_CLASS_DEMON_HUNTER,
        self::CHARACTER_CLASS_EVOKER,
    ];

    /**
     * @return HasMany
     */
    public function specializations(): HasMany
    {
        return $this->hasMany(CharacterClassSpecialization::class);
    }

    /**
     * @return BelongsToMany
     */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(CharacterRace::class, 'character_race_class_couplings');
    }

    /**
     * @return BelongsToMany
     */
    public function dungeonrouteplayerclasses(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerClass::class, 'dungeon_route_player_classes');
    }

    /**
     * @return BelongsToMany
     */
    public function dungeonrouteplayerraces(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerRace::class, 'dungeon_route_player_races');
    }

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
