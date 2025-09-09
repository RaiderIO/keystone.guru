<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int                                      $id
 * @property int                                      $race_id Blizzard race ID
 * @property string                                   $key
 * @property string                                   $name
 * @property int                                      $faction_id
 *
 * @property Faction                                  $faction
 * @property Collection<CharacterClass>               $classes
 * @property Collection<CharacterClassSpecialization> $specializations
 * @property Collection<CharacterClassSpecialization> $dungeonRoutePlayerRace
 *
 * @mixin Eloquent
 */
class CharacterRace extends CacheModel
{
    use SeederModel;

    public const CHARACTER_RACE_HUMAN               = 'human';
    public const CHARACTER_RACE_DWARF               = 'dwarf';
    public const CHARACTER_RACE_NIGHT_ELF           = 'night_elf';
    public const CHARACTER_RACE_GNOME               = 'gnome';
    public const CHARACTER_RACE_DRAENEI             = 'draenei';
    public const CHARACTER_RACE_WORGEN              = 'worgen';
    public const CHARACTER_RACE_PANDAREN_ALLIANCE   = 'pandarenalliance';
    public const CHARACTER_RACE_VOID_ELF            = 'void_elf';
    public const CHARACTER_RACE_LIGHTFORGED_DRAENEI = 'lightforged_draenei';
    public const CHARACTER_RACE_DARK_IRON_DWARF     = 'dark_iron_dwarf';
    public const CHARACTER_RACE_DRACTHYR_ALLIANCE   = 'dracthyralliance';

    public const CHARACTER_RACE_ORC                 = 'orc';
    public const CHARACTER_RACE_UNDEAD              = 'undead';
    public const CHARACTER_RACE_TAUREN              = 'tauren';
    public const CHARACTER_RACE_TROLL               = 'troll';
    public const CHARACTER_RACE_BLOOD_ELF           = 'blood_elf';
    public const CHARACTER_RACE_GOBLIN              = 'goblin';
    public const CHARACTER_RACE_PANDAREN_HORDE      = 'pandarenhorde';
    public const CHARACTER_RACE_NIGHTBORNE          = 'nightborne';
    public const CHARACTER_RACE_HIGHMOUNTAIN_TAUREN = 'highmountain_tauren';
    public const CHARACTER_RACE_MAGHAR_ORC          = 'maghar_orc';
    public const CHARACTER_RACE_DRACTHYR_HORDE      = 'dracthyrhorde';

    public const CHARACTER_RACE_KUL_TIRAN_HUMAN = 'kul_tiran_human';
    public const CHARACTER_RACE_ZANDALARI_TROLL = 'zandalari_troll';

    public const CHARACTER_RACE_MECHAGNOME = 'mechagnome';
    public const CHARACTER_RACE_VULPERA    = 'vulpera';

    public const CHARACTER_RACE_EARTHEN_ALLIANCE = 'earthenalliance';
    public const CHARACTER_RACE_EARTHEN_HORDE    = 'earthenhorde';

    // ALL constant containing all races
    public const ALL = [
        self::CHARACTER_RACE_HUMAN,
        self::CHARACTER_RACE_DWARF,
        self::CHARACTER_RACE_NIGHT_ELF,
        self::CHARACTER_RACE_GNOME,
        self::CHARACTER_RACE_DRAENEI,
        self::CHARACTER_RACE_WORGEN,
        self::CHARACTER_RACE_PANDAREN_ALLIANCE,
        self::CHARACTER_RACE_VOID_ELF,
        self::CHARACTER_RACE_LIGHTFORGED_DRAENEI,
        self::CHARACTER_RACE_DARK_IRON_DWARF,
        self::CHARACTER_RACE_DRACTHYR_ALLIANCE,
        self::CHARACTER_RACE_ORC,
        self::CHARACTER_RACE_UNDEAD,
        self::CHARACTER_RACE_TAUREN,
        self::CHARACTER_RACE_TROLL,
        self::CHARACTER_RACE_BLOOD_ELF,
        self::CHARACTER_RACE_GOBLIN,
        self::CHARACTER_RACE_PANDAREN_HORDE,
        self::CHARACTER_RACE_NIGHTBORNE,
        self::CHARACTER_RACE_HIGHMOUNTAIN_TAUREN,
        self::CHARACTER_RACE_MAGHAR_ORC,
        self::CHARACTER_RACE_DRACTHYR_HORDE,
        self::CHARACTER_RACE_KUL_TIRAN_HUMAN,
        self::CHARACTER_RACE_ZANDALARI_TROLL,
        self::CHARACTER_RACE_MECHAGNOME,
        self::CHARACTER_RACE_VULPERA,
        self::CHARACTER_RACE_EARTHEN_ALLIANCE,
        self::CHARACTER_RACE_EARTHEN_HORDE,
    ];

    public $timestamps = false;

    public $hidden = [
        'icon_file_id',
        'pivot',
    ];

    public $fillable = [
        'race_id',
        'key',
        'name',
        'faction_id',
    ];

    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(CharacterClass::class, 'character_race_class_couplings');
    }

    public function specializations(): HasMany
    {
        return $this->hasMany(CharacterClass::class);
    }

    public function dungeonRoutePlayerRace(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerRace::class);
    }
}
