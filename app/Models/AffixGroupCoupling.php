<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property $id int The ID of this Affix.
 * @property $affix_id int
 * @property $affix_group_id int
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
        return $this->hasOne('App\Models\Affix');
    }

    /**
     * @return HasOne
     */
    public function affixgroup(): HasOne
    {
        return $this->hasOne('App\Models\AffixGroup');
    }
}
