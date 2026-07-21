<?php

namespace App\Models\Mapping;

use App\Models\Traits\HasGenericModelRelation;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $dungeon_id
 * @property int         $model_id
 * @property string      $model_class
 * @property string      $before_model
 * @property string|null $after_model
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @mixin Eloquent
 */
class MappingChangeLog extends Model
{
    use HasGenericModelRelation;
    use SeederModel;

    protected $fillable = [
        'dungeon_id',
        'model_id',
        'model_class',
        'before_model',
        'after_model',
    ];
}
