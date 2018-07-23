<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int The ID of this Affix.
 * @property $icon_file_id int The file ID of the icon associated with this Affix.
 * @property $name string The name of the Affix.
 * @property $description string The description of this Affix.
 */
class Affix extends Model
{
    public $hidden = ['pivot'];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function iconfile()
    {
        return $this->hasOne('App\Models\File', 'model_id')->where('model_class', '=', get_class($this));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function affixGroups()
    {
        return $this->belongsToMany('App\Models\AffixGroup', 'affix_group_couplings');
    }
}
