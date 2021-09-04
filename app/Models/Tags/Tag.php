<?php

namespace App\Models\Tags;

use App\Http\Requests\Tag\TagFormRequest;
use App\Models\DungeonRoute;
use App\Models\Traits\HasGenericModelRelation;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
            case TagCategory::DUNGEON_ROUTE_PERSONAL:
            case TagCategory::DUNGEON_ROUTE_TEAM:
                // Find all routes that match the name of this tag
                $result = DungeonRoute::join('tags', 'tags.model_id', '=', 'dungeon_routes.id')
                    ->where('tags.model_class', $this->model_class)
                    ->where('tags.name', $this->name)
                    ->where('tags.user_id', $this->user_id)
                    ->where('tags.tag_category_id', $this->tag_category_id)
                    ->get();
                break;
        }

        return $result;
    }

    /**
     * @param TagFormRequest $request
     * @param int $tagCategoryId
     * @return Tag
     */
    public static function saveFromRequest(TagFormRequest $request, int $tagCategoryId): Tag
    {
        // Bit strange - but required with multiple forms existing on the profile page
        $name = $request->get('tag_name_new');

        // Save the tag we're trying to add
        $tag = new Tag();
        // Technically we can fetch the user_id by going through the model but that's just too much work and slow
        $tag->user_id         = Auth::id();
        $tag->tag_category_id = $tagCategoryId;
        $tag->model_id        = null;
        $tag->model_class     = null;
        $tag->name            = $name;
        $tag->color           = null;

        $tag->save();

        return $tag;
    }
}
