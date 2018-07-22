<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this Affix.
 * @property $affix_id int
 * @property $affix_group_id int
 */
class AffixGroupCoupling extends Model
{

    public $timestamps = false;
    
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
