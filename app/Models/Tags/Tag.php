<?php

namespace App\Models\Tags;

use App\Models\Traits\HasGenericModelRelation;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $tag_category_id
 * @property int $model_id
 * @property string $model_class
 * @property string $name
 * @property string|null $color
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property TagCategory $tagcategory
 *
 * @mixin Eloquent
 */
class Tag extends Model
{
    use HasGenericModelRelation;

    protected $visible = ['id', 'name', 'color'];

    /**
     * @return HasOne
     */
    public function tagcategory()
    {
        return $this->hasOne('App\Models\Tags\TagCategory', 'tag_category_id');
    }
}
