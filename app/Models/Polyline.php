<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $model_id int
 * @property $model_class string
 * @property $color string
 * @property $color_animated string
 * @property $weight int
 * @property $vertices_json string JSON encoded vertices
 *
 * @property Model $model
 *
 * @mixin Eloquent
 */
class Polyline extends Model
{
    public $timestamps = false;
    public $visible = ['color', 'color_animated', 'weight', 'vertices_json'];
    public $fillable = ['model_id', 'model_class', 'color', 'color_animated', 'weight', 'vertices_json'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function model()
    {
        return $this->hasOne($this->model_class, 'model_id');
    }
}
