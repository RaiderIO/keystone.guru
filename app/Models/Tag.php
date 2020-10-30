<?php

namespace App\Models;

use App\Models\Traits\HasGenericModelRelation;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $model_id
 * @property string $model_class
 * @property string $tag
 *
 * @mixin Eloquent
 */
class Tag extends Model
{
    use HasGenericModelRelation;

    public $timestamps = false;
}
