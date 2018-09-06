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

    /**
     * @return bool Checks if this group contains the Teeming affix.
     */
    public function isTeeming()
    {
        $result = false;

        foreach ($this->affixes as $affix) {
            /** @var $affix Affix */
            // A bit hacky I guess? But Teeming is such a special case that I'm justifying this magic string
            if ($result = ($affix->name === 'Teeming')) {
                break;
            }
        }

        return $result;
    }
}
