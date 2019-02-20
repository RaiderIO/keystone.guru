<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id int
 * @property $model_id int
 * @property $model_class string
 * @property $color string
 * @property $weight int
 * @property $model Model
 * @property $vertices_json string JSON encoded vertices
 *
 * @mixin \Eloquent
 */
class Polyline extends Model
{
    public $timestamps = false;
    public $visible = ['color', 'weight', 'vertices_json'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function model()
    {
        return $this->hasOne($this->model_class, 'model_id');
    }
}
