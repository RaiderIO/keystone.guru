<?php


namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait Reportable
{
    /**
     * @return HasMany
     */
    function userreports()
    {
        return $this->hasMany('App\Models\UserReport', 'model_id')->where('model_class', get_class($this));
    }
}