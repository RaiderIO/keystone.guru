<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this Affix.
 * @property $randomcolumn int
 */
class AffixGroup extends Model
{

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function affixes()
    {
        return $this->belongsToMany('App\Models\Affix', 'affix_group_couplings');
    }
}
