<?php

namespace App\Models;

/**
 * @property $id int The ID of this Affix.
 * @property $icon_file_id int The file ID of the icon associated with this Affix.
 * @property $name string The name of the Affix.
 * @property $description string The description of this Affix.
 *
 * @mixin \Eloquent
 */
class Affix extends IconFileModel
{
    public $hidden = ['icon_file_id', 'pivot'];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function affixGroups()
    {
        return $this->belongsToMany('App\Models\AffixGroup', 'affix_group_couplings');
    }
}
