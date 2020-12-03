<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $model_class
 *
 * @mixin Model
 */
trait HasGenericModelRelation
{
    /**
     * @return HasOne
     */
    function model()
    {
        return $this->hasOne($this->model_class, 'model_id');
    }
}