<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property class-string $model_class
 * @property Model        $model
 *
 * @mixin Model
 */
trait HasGenericModelRelation
{
    /** @return HasOne<Model, $this> */
    public function model(): HasOne
    {
        /** @phpstan-ignore argument.templateType */
        return $this->hasOne($this->model_class, 'id', 'model_id');
    }
}
