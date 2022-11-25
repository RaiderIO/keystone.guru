<?php

namespace App\Models\AffixGroup;

use App\Models\Affix;
use App\Models\CacheModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id The ID of this Affix.
 * @property int $affix_id
 * @property int $affix_group_id
 *
 * @property Affix $affix
 * @property AffixGroup $affixgroup
 *
 * @mixin Eloquent
 */
class AffixGroupCoupling extends CacheModel
{
    public $timestamps = false;
    protected $fillable = ['affix_id', 'affix_group_id'];

    /**
     * @return HasOne
     */
    public function affix(): HasOne
    {
        return $this->hasOne(Affix::class);
    }

    /**
     * @return HasOne
     */
    public function affixgroup(): HasOne
    {
        return $this->hasOne(AffixGroup::class);
    }
}
