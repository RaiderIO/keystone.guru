<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $model_class
 *
 * @property Model $model
 *
 * @mixin Model
 */
trait HasGenericModelRelation
{
    /**
     * @return HasOne
     */
    public function model()
    {
        return $this->hasOne($this->model_class, 'id', 'model_id');
    }
}
