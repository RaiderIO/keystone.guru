<?php

namespace App\Models\Tags;

use App\Models\Traits\HasGenericModelRelation;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $tag_id
 * @property int $model_id
 * @property string $model_class
 * @property string|null $color
 *
 * @property Tag $tag
 *
 * @mixin Eloquent
 */
class TagModel extends Model
{
    use HasGenericModelRelation;

    public $timestamps = false;

    protected $visible = ['id', 'tag', 'color'];
    protected $with = ['tag'];


    /**
     * @return BelongsTo
     */
    function tag()
    {
        return $this->belongsTo('App\Models\Tags\Tag');
    }
}
