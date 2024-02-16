<?php

namespace App\Models;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Traits\HasIconFile;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int    $id The ID of this Affix.
 * @property int    $icon_file_id The file ID of the icon associated with this Affix.
 * @property int    $affix_id The ID of the affix in-game.
 * @property string $key The identifying key of the Affix.
 * @property string $name The name of the Affix.
 * @property string $description The description of this Affix.
 *
 * @mixin Eloquent
 */
class Affix extends CacheModel
{
    const AFFIX_BOLSTERING  = 'Bolstering';
    const AFFIX_BURSTING    = 'Bursting';
    const AFFIX_EXPLOSIVE   = 'Explosive';
    const AFFIX_FORTIFIED   = 'Fortified';
    const AFFIX_GRIEVOUS    = 'Grievous';
    const AFFIX_INFESTED    = 'Infested';
    const AFFIX_NECROTIC    = 'Necrotic';
    const AFFIX_QUAKING     = 'Quaking';
    const AFFIX_RAGING      = 'Raging';
    const AFFIX_RELENTLESS  = 'Relentless';
    const AFFIX_SANGUINE    = 'Sanguine';
    const AFFIX_SKITTISH    = 'Skittish';
    const AFFIX_TEEMING     = 'Teeming';
    const AFFIX_TYRANNICAL  = 'Tyrannical';
    const AFFIX_VOLCANIC    = 'Volcanic';
    const AFFIX_REAPING     = 'Reaping';
    const AFFIX_BEGUILING   = 'Beguiling';
    const AFFIX_AWAKENED    = 'Awakened';
    const AFFIX_INSPIRING   = 'Inspiring';
    const AFFIX_SPITEFUL    = 'Spiteful';
    const AFFIX_STORMING    = 'Storming';
    const AFFIX_PRIDEFUL    = 'Prideful';
    const AFFIX_TORMENTED   = 'Tormented';
    const AFFIX_UNKNOWN     = 'Unknown';
    const AFFIX_INFERNAL    = 'Infernal';
    const AFFIX_ENCRYPTED   = 'Encrypted';
    const AFFIX_SHROUDED    = 'Shrouded';
    const AFFIX_THUNDERING  = 'Thundering';
    const AFFIX_AFFLICTED   = 'Afflicted';
    const AFFIX_ENTANGLING  = 'Entangling';
    const AFFIX_INCORPOREAL = 'Incorporeal';

    const ALL = [
        self::AFFIX_BOLSTERING  => 1,
        self::AFFIX_BURSTING    => 2,
        self::AFFIX_EXPLOSIVE   => 3,
        self::AFFIX_FORTIFIED   => 4,
        self::AFFIX_GRIEVOUS    => 5,
        self::AFFIX_INFESTED    => 6,
        self::AFFIX_NECROTIC    => 7,
        self::AFFIX_QUAKING     => 8,
        self::AFFIX_RAGING      => 9,
        self::AFFIX_RELENTLESS  => 10,
        self::AFFIX_SANGUINE    => 11,
        self::AFFIX_SKITTISH    => 12,
        self::AFFIX_TEEMING     => 13,
        self::AFFIX_TYRANNICAL  => 14,
        self::AFFIX_VOLCANIC    => 15,
        self::AFFIX_REAPING     => 16,
        self::AFFIX_BEGUILING   => 17,
        self::AFFIX_AWAKENED    => 18,
        self::AFFIX_INSPIRING   => 19,
        self::AFFIX_SPITEFUL    => 20,
        self::AFFIX_STORMING    => 21,
        self::AFFIX_PRIDEFUL    => 22,
        self::AFFIX_TORMENTED   => 23,
        self::AFFIX_UNKNOWN     => 24,
        self::AFFIX_INFERNAL    => 25,
        self::AFFIX_ENCRYPTED   => 26,
        self::AFFIX_SHROUDED    => 27,
        self::AFFIX_THUNDERING  => 28,
        self::AFFIX_AFFLICTED   => 29,
        self::AFFIX_ENTANGLING  => 30,
        self::AFFIX_INCORPOREAL => 31,
    ];

    const SEASONAL_AFFIXES = [
        self::AFFIX_REAPING,
        self::AFFIX_BEGUILING,
        self::AFFIX_AWAKENED,
        self::AFFIX_PRIDEFUL,
        self::AFFIX_TORMENTED,
        self::AFFIX_INFERNAL,
        self::AFFIX_ENCRYPTED,
        self::AFFIX_SHROUDED,
        self::AFFIX_THUNDERING,
    ];

    private const SEASONAL_TYPE_AFFIX_MAPPING = [
        Enemy::SEASONAL_TYPE_SHROUDED           => Affix::AFFIX_SHROUDED,
        Enemy::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX => Affix::AFFIX_SHROUDED,
        Enemy::SEASONAL_TYPE_ENCRYPTED          => Affix::AFFIX_ENCRYPTED,
        Enemy::SEASONAL_TYPE_TORMENTED          => Affix::AFFIX_TORMENTED,
        Enemy::SEASONAL_TYPE_PRIDEFUL           => Affix::AFFIX_PRIDEFUL,
    ];

    use SeederModel;
    use HasIconFile;

    public $hidden = ['icon_file_id', 'pivot'];

    public $timestamps = false;

    /**
     * @return BelongsToMany
     */
    public function affixGroups(): BelongsToMany
    {
        return $this->belongsToMany(AffixGroup::class, 'affix_group_couplings');
    }

    /**
     * @param string $seasonalType
     * @return string|null
     */
    public static function getAffixBySeasonalType(string $seasonalType): ?string
    {
        return self::SEASONAL_TYPE_AFFIX_MAPPING[$seasonalType] ?? null;
    }
}
