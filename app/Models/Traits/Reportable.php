<?php


namespace App\Models\Traits;

trait Reportable
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function userreports()
    {
        return $this->hasMany('App\Models\UserReport', 'model_id')->where('model_class', get_class($this));
    }
}