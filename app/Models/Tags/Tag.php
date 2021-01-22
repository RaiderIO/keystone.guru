<?php

namespace App\Models\Tags;

use App\Models\Traits\HasGenericModelRelation;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
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
 * @method Builder unique(TagCategory $tagCategory)
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

    /**
     * @param Builder $query
     * @param TagCategory|null $category
     * @return Builder
     */
    public function scopeUnique(Builder $query, ?TagCategory $category = null)
    {
        if ($category instanceof TagCategory) {
            $query = $query->where('tag_category_id', $category->id);
        }

        return $query->groupBy('name');
    }
}
