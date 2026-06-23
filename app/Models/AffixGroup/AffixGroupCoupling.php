<?php

namespace App\Models\AffixGroup;

use App\Models\Affix;
use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $affix_id
 * @property int $affix_group_id
 * @property int $key_level
 *
 * @property Affix      $affix
 * @property AffixGroup $affixGroup
 *
 * @mixin Eloquent
 */
class AffixGroupCoupling extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'affix_id',
        'affix_group_id',
        'key_level',
    ];

    /** @return HasOne<Affix, $this> */
    public function affix(): HasOne
    {
        return $this->hasOne(Affix::class);
    }

    /** @return HasOne<AffixGroup, $this> */
    public function affixGroup(): HasOne
    {
        return $this->hasOne(AffixGroup::class);
    }
}
