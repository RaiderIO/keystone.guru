<?php

namespace App\Models\Mapping;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $model_id
 * @property string $model_class
 * @property string $before_model
 * @property string|null $after_model
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class MappingChangeLog extends Model
{
    protected $fillable = ['model_id', 'model_class', 'before_model', 'after_model'];

    /**
     * @return HasOne
     */
    function model()
    {
        return $this->hasOne($this->model_class, 'model_id');
    }
}
