<?php

namespace App\Models;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Traits\HasIconFile;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Str;

/**
 * @property int                    $id The ID of this Affix.
 * @property int                    $icon_file_id The file ID of the icon associated with this Affix.
 * @property int                    $affix_id The ID of the affix in-game.
 * @property string                 $key The identifying key of the Affix.
 * @property string                 $name The name of the Affix.
 * @property string                 $description The description of this Affix.
 *
 * @property string                 $image_name The name of the image of this Affix (appended).
 * @property string                 $image_url The URL to the image of this Affix (appended).
 *
 * @property Collection<AffixGroup> $affixGroups
 *
 * @mixin Eloquent
 */
class Affix extends CacheModel
{
    use HasIconFile;
    use SeederModel;

    public $hidden = ['icon_file_id', 'pivot'];

    public $timestamps = false;

    protected $fillable = ['id', 'icon_file_id', 'affix_id', 'key', 'name', 'description'];

    protected $appends = ['image_name', 'image_url'];

    public const AFFIX_BOLSTERING                  = 'Bolstering';
    public const AFFIX_BURSTING                    = 'Bursting';
    public const AFFIX_EXPLOSIVE                   = 'Explosive';
    public const AFFIX_FORTIFIED                   = 'Fortified';
    public const AFFIX_GRIEVOUS                    = 'Grievous';
    public const AFFIX_INFESTED                    = 'Infested';
    public const AFFIX_NECROTIC                    = 'Necrotic';
    public const AFFIX_QUAKING                     = 'Quaking';
    public const AFFIX_RAGING                      = 'Raging';
    public const AFFIX_RELENTLESS                  = 'Relentless';
    public const AFFIX_SANGUINE                    = 'Sanguine';
    public const AFFIX_SKITTISH                    = 'Skittish';
    public const AFFIX_TEEMING                     = 'Teeming';
    public const AFFIX_TYRANNICAL                  = 'Tyrannical';
    public const AFFIX_VOLCANIC                    = 'Volcanic';
    public const AFFIX_REAPING                     = 'Reaping';
    public const AFFIX_BEGUILING                   = 'Beguiling';
    public const AFFIX_AWAKENED                    = 'Awakened';
    public const AFFIX_INSPIRING                   = 'Inspiring';
    public const AFFIX_SPITEFUL                    = 'Spiteful';
    public const AFFIX_STORMING                    = 'Storming';
    public const AFFIX_PRIDEFUL                    = 'Prideful';
    public const AFFIX_TORMENTED                   = 'Tormented';
    public const AFFIX_UNKNOWN                     = 'Unknown';
    public const AFFIX_INFERNAL                    = 'Infernal';
    public const AFFIX_ENCRYPTED                   = 'Encrypted';
    public const AFFIX_SHROUDED                    = 'Shrouded';
    public const AFFIX_THUNDERING                  = 'Thundering';
    public const AFFIX_AFFLICTED                   = 'Afflicted';
    public const AFFIX_ENTANGLING                  = 'Entangling';
    public const AFFIX_INCORPOREAL                 = 'Incorporeal';
    public const AFFIX_XALATATHS_BARGAIN_ASCENDANT = 'Xal\'atath\'s Bargain: Ascendant';
    public const AFFIX_XALATATHS_BARGAIN_DEVOUR    = 'Xal\'atath\'s Bargain: Devour';
    public const AFFIX_XALATATHS_BARGAIN_VOIDBOUND = 'Xal\'atath\'s Bargain: Voidbound';
    public const AFFIX_XALATATHS_BARGAIN_OBLIVION  = 'Xal\'atath\'s Bargain: Oblivion';
    public const AFFIX_XALATATHS_BARGAIN_FRENZIED  = 'Xal\'atath\'s Bargain: Frenzied';
    public const AFFIX_XALATATHS_GUILE             = 'Xal\'atath\'s Guile';
    public const AFFIX_CHALLENGERS_PERIL           = 'Challenger\'s Peril';
    public const AFFIX_XALATATHS_BARGAIN_PULSAR    = 'Xal\'atath\'s Bargain: Pulsar';


    public const ALL = [
        self::AFFIX_BOLSTERING                  => 1,
        self::AFFIX_BURSTING                    => 2,
        self::AFFIX_EXPLOSIVE                   => 3,
        self::AFFIX_FORTIFIED                   => 4,
        self::AFFIX_GRIEVOUS                    => 5,
        self::AFFIX_INFESTED                    => 6,
        self::AFFIX_NECROTIC                    => 7,
        self::AFFIX_QUAKING                     => 8,
        self::AFFIX_RAGING                      => 9,
        self::AFFIX_RELENTLESS                  => 10,
        self::AFFIX_SANGUINE                    => 11,
        self::AFFIX_SKITTISH                    => 12,
        self::AFFIX_TEEMING                     => 13,
        self::AFFIX_TYRANNICAL                  => 14,
        self::AFFIX_VOLCANIC                    => 15,
        self::AFFIX_REAPING                     => 16,
        self::AFFIX_BEGUILING                   => 17,
        self::AFFIX_AWAKENED                    => 18,
        self::AFFIX_INSPIRING                   => 19,
        self::AFFIX_SPITEFUL                    => 20,
        self::AFFIX_STORMING                    => 21,
        self::AFFIX_PRIDEFUL                    => 22,
        self::AFFIX_TORMENTED                   => 23,
        self::AFFIX_UNKNOWN                     => 24,
        self::AFFIX_INFERNAL                    => 25,
        self::AFFIX_ENCRYPTED                   => 26,
        self::AFFIX_SHROUDED                    => 27,
        self::AFFIX_THUNDERING                  => 28,
        self::AFFIX_AFFLICTED                   => 29,
        self::AFFIX_ENTANGLING                  => 30,
        self::AFFIX_INCORPOREAL                 => 31,
        self::AFFIX_XALATATHS_BARGAIN_ASCENDANT => 32,
        self::AFFIX_XALATATHS_BARGAIN_DEVOUR    => 33,
        self::AFFIX_XALATATHS_BARGAIN_VOIDBOUND => 34,
        self::AFFIX_XALATATHS_BARGAIN_OBLIVION  => 35,
        self::AFFIX_XALATATHS_BARGAIN_FRENZIED  => 36,
        self::AFFIX_XALATATHS_GUILE             => 37,
        self::AFFIX_CHALLENGERS_PERIL           => 38,
        self::AFFIX_XALATATHS_BARGAIN_PULSAR    => 39,
    ];

    public const SEASONAL_AFFIXES = [
        self::AFFIX_REAPING,
        self::AFFIX_BEGUILING,
        self::AFFIX_AWAKENED,
        self::AFFIX_PRIDEFUL,
        self::AFFIX_TORMENTED,
        self::AFFIX_INFERNAL,
        self::AFFIX_ENCRYPTED,
        self::AFFIX_SHROUDED,
        self::AFFIX_THUNDERING,
        self::AFFIX_XALATATHS_BARGAIN_ASCENDANT,
        self::AFFIX_XALATATHS_BARGAIN_DEVOUR,
        self::AFFIX_XALATATHS_BARGAIN_VOIDBOUND,
        self::AFFIX_XALATATHS_BARGAIN_OBLIVION,
        self::AFFIX_XALATATHS_BARGAIN_FRENZIED,
        self::AFFIX_XALATATHS_GUILE,
        self::AFFIX_XALATATHS_BARGAIN_PULSAR,
    ];

    private const SEASONAL_TYPE_AFFIX_MAPPING = [
        Enemy::SEASONAL_TYPE_SHROUDED           => Affix::AFFIX_SHROUDED,
        Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX => Affix::AFFIX_SHROUDED,
        Enemy::SEASONAL_TYPE_ENCRYPTED          => Affix::AFFIX_ENCRYPTED,
        Enemy::SEASONAL_TYPE_TORMENTED          => Affix::AFFIX_TORMENTED,
        Enemy::SEASONAL_TYPE_PRIDEFUL           => Affix::AFFIX_PRIDEFUL,
    ];

    public function getImageNameAttribute(): string
    {
        return Str::slug($this->key, '_');
    }

    public function getImageUrlAttribute(): string
    {
        return sprintf('images/affixes/%s.jpg', $this->getImageNameAttribute());
    }

    public function affixGroups(): BelongsToMany
    {
        return $this->belongsToMany(AffixGroup::class, 'affix_group_couplings');
    }

    public static function getAffixBySeasonalType(string $seasonalType): ?string
    {
        return self::SEASONAL_TYPE_AFFIX_MAPPING[$seasonalType] ?? null;
    }
}
