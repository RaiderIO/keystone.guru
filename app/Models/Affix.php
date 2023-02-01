<?php

namespace App\Models;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Traits\HasIconFile;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property $id int The ID of this Affix.
 * @property $icon_file_id int The file ID of the icon associated with this Affix.
 * @property $key string The identifying key of the Affix.
 * @property $name string The name of the Affix.
 * @property $description string The description of this Affix.
 *
 * @mixin Eloquent
 */
class Affix extends CacheModel
{
    const AFFIX_BOLSTERING = 'Bolstering';
    const AFFIX_BURSTING   = 'Bursting';
    const AFFIX_EXPLOSIVE  = 'Explosive';
    const AFFIX_FORTIFIED  = 'Fortified';
    const AFFIX_GRIEVOUS   = 'Grievous';
    const AFFIX_INFESTED   = 'Infested';
    const AFFIX_NECROTIC   = 'Necrotic';
    const AFFIX_QUAKING    = 'Quaking';
    const AFFIX_RAGING     = 'Raging';
    const AFFIX_RELENTLESS = 'Relentless';
    const AFFIX_SANGUINE   = 'Sanguine';
    const AFFIX_SKITTISH   = 'Skittish';
    const AFFIX_TEEMING    = 'Teeming';
    const AFFIX_TYRANNICAL = 'Tyrannical';
    const AFFIX_VOLCANIC   = 'Volcanic';
    const AFFIX_REAPING    = 'Reaping';
    const AFFIX_BEGUILING  = 'Beguiling';
    const AFFIX_AWAKENED   = 'Awakened';
    const AFFIX_INSPIRING  = 'Inspiring';
    const AFFIX_SPITEFUL   = 'Spiteful';
    const AFFIX_STORMING   = 'Storming';
    const AFFIX_PRIDEFUL   = 'Prideful';
    const AFFIX_TORMENTED  = 'Tormented';
    const AFFIX_UNKNOWN    = 'Unknown';
    const AFFIX_INFERNAL   = 'Infernal';
    const AFFIX_ENCRYPTED  = 'Encrypted';
    const AFFIX_SHROUDED   = 'Shrouded';
    const AFFIX_THUNDERING = 'Thundering';

    const ALL_AFFIXES = [
        self::AFFIX_BOLSTERING,
        self::AFFIX_BURSTING,
        self::AFFIX_EXPLOSIVE,
        self::AFFIX_FORTIFIED,
        self::AFFIX_GRIEVOUS,
        self::AFFIX_INFESTED,
        self::AFFIX_NECROTIC,
        self::AFFIX_QUAKING,
        self::AFFIX_RAGING,
        self::AFFIX_RELENTLESS,
        self::AFFIX_SANGUINE,
        self::AFFIX_SKITTISH,
        self::AFFIX_TEEMING,
        self::AFFIX_TYRANNICAL,
        self::AFFIX_VOLCANIC,
        self::AFFIX_REAPING,
        self::AFFIX_BEGUILING,
        self::AFFIX_AWAKENED,
        self::AFFIX_INSPIRING,
        self::AFFIX_SPITEFUL,
        self::AFFIX_STORMING,
        self::AFFIX_PRIDEFUL,
        self::AFFIX_TORMENTED,
        self::AFFIX_UNKNOWN,
        self::AFFIX_INFERNAL,
        self::AFFIX_ENCRYPTED,
        self::AFFIX_SHROUDED,
        self::AFFIX_THUNDERING,
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
