<?php

namespace App\Models;

use App\Models\Traits\HasGenericModelRelation;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $model_id
 * @property string $model_class
 * @property string $color
 * @property string|null $color_animated
 * @property int $weight
 * @property string $vertices_json JSON encoded vertices
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
