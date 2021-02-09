<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property $id int The ID of this Affix.
 * @property $icon_file_id int The file ID of the icon associated with this Affix.
 * @property $name string The name of the Affix.
 * @property $description string The description of this Affix.
 *
 * @mixin Eloquent
 */
class Affix extends CacheModel
{
    use HasIconFile;

    public $hidden = ['icon_file_id', 'pivot'];

    public $timestamps = false;

    /**
     * @return BelongsToMany
     */
    public function affixGroups()
    {
        return $this->belongsToMany('App\Models\AffixGroup', 'affix_group_couplings');
    }
}
