<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $model_id
 * @property string $model_class
 * @property string $color
 * @property int $weight
 * @property string $vertices_json JSON encoded vertices
 *
 * @property Model $model
 *
 * @mixin \Eloquent
 */
class Polyline extends Model
{
    public $timestamps = false;
    public $visible = ['color', 'weight', 'vertices_json'];
    public $fillable = ['model_id', 'model_class', 'color', 'weight', 'vertices_json'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function model()
    {
        return $this->hasOne($this->model_class, 'model_id');
    }
}
