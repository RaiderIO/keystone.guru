<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\Traits\HasIconFile;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int    $id
 * @property int    $class_id Blizzard class ID
 * @property string $key
 * @property string $name
 * @property string $color
 *
 * @property Collection<CharacterClassSpecialization> $specializations
 * @property Collection<DungeonRoutePlayerClass>      $dungeonRoutePlayerClasses
 * @property Collection<DungeonRoutePlayerRace>       $dungeonRoutePlayerRaces
 *
 * @mixin Eloquent
 */
class CharacterClass extends CacheModel
{
    use HasIconFile;
    use SeederModel;

    public $timestamps = false;

    public $hidden = [
        'icon_file_id',
        'pivot',
    ];

    public $fillable = [
        'class_id',
        'key',
        'name',
        'color',
        'icon_file_id',
    ];

    public const CHARACTER_CLASS_WARRIOR      = 'warrior';
    public const CHARACTER_CLASS_HUNTER       = 'hunter';
    public const CHARACTER_CLASS_DEATH_KNIGHT = 'death_knight';
    public const CHARACTER_CLASS_MAGE         = 'mage';
    public const CHARACTER_CLASS_PRIEST       = 'priest';
    public const CHARACTER_CLASS_MONK         = 'monk';
    public const CHARACTER_CLASS_ROGUE        = 'rogue';
    public const CHARACTER_CLASS_WARLOCK      = 'warlock';
    public const CHARACTER_CLASS_SHAMAN       = 'shaman';
    public const CHARACTER_CLASS_PALADIN      = 'paladin';
    public const CHARACTER_CLASS_DRUID        = 'druid';
    public const CHARACTER_CLASS_DEMON_HUNTER = 'demon_hunter';
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

    public function specializations(): HasMany
    {
        return $this->hasMany(CharacterClassSpecialization::class);
    }

    public function races(): BelongsToMany
    {
        return $this->belongsToMany(CharacterRace::class, 'character_race_class_couplings');
    }

    public function dungeonRoutePlayerClasses(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerClass::class);
    }

    public function dungeonRoutePlayerRaces(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerRace::class);
    }
}
