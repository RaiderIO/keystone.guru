<?php

namespace App\Models\Tags;

use App\Models\DungeonRoute;
use App\Models\Traits\HasGenericModelRelation;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

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
     * @return BelongsTo
     */
    public function tagcategory()
    {
        return $this->belongsTo('App\Models\Tags\TagCategory', 'tag_category_id');
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

    /**
     * @return Collection
     */
    public function getUsage(): Collection
    {
        $result = new Collection();
        switch ($this->tagcategory->name) {
            case TagCategory::DUNGEON_ROUTE:
                // Find all routes that match the name of this tag
                $result = DungeonRoute::join('tags', 'tags.model_id', '=', 'dungeon_routes.id')
                    ->where('tags.model_class', $this->model_class)
                    ->where('tags.user_id', $this->user_id)
                    ->where('tags.name', $this->name)
                    ->get();
                break;
        }

        return $result;
    }
}
