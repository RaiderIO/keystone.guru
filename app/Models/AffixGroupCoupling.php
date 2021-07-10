<?php

namespace App\Models;

/**
 * @property $id int The ID of this Affix.
 * @property $affix_id int
 * @property $affix_group_id int
 *
 * @mixin \Eloquent
 */
class AffixGroupCoupling extends CacheModel
{
    public $timestamps = false;
    protected $fillable = ['affix_id', 'affix_group_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function affix()
    {
        return $this->hasOne('App\Models\Affix');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function affixGroup()
    {
        return $this->hasOne('App\Models\AffixGroup');
    }
}
