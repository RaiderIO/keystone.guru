<?php

namespace App\Models;

use App\Models\Traits\HasGenericModelRelation;
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
    use HasGenericModelRelation;

    public $timestamps = false;
    public $visible = ['color', 'color_animated', 'weight', 'vertices_json'];
    public $fillable = ['model_id', 'model_class', 'color', 'color_animated', 'weight', 'vertices_json'];
}
