<?php

namespace App\Models\Traits;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property File $iconfile
 *
 * @mixin Model
 */
trait HasIconFile
{
    /**
     * @return HasOne
     */
    function iconfile()
    {
        return $this->hasOne('App\Models\File', 'model_id')->where('model_class', '=', get_class($this));
    }
}