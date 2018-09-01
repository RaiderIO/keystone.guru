<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this Affix.
 * @property $randomcolumn int
 * @property $affix \Illuminate\Database\Eloquent\Collection
 */
class AffixGroup extends Model
{

    public $timestamps = false;
    public $with = ['affixes'];
    public $hidden = ['pivot'];
    protected $appends = ['text'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function affixes()
    {
        return $this->belongsToMany('App\Models\Affix', 'affix_group_couplings');
    }

    /**
     * @return string The text representation of this affix group.
     */
    public function getTextAttribute()
    {
        $result = [];
        foreach ($this->affixes as $affix) {
            /** @var $affix Affix */
            $result[] = $affix->name;
        }
        return implode(', ', $result);
    }
}
