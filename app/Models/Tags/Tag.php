<?php

namespace App\Models\Tags;

use App\Http\Requests\Tag\TagFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Traits\HasGenericModelRelation;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * @property int         $id
 * @property int         $user_id
 * @property int         $tag_category_id
 * @property int         $model_id
 * @property string      $model_class
 * @property string      $name
 * @property string|null $color
 * @property Carbon      $updated_at
 * @property Carbon      $created_at
 * @property TagCategory $tagCategory
 *
 * @method Builder unique(?int $tagCategoryId)
 *
 * @mixin Eloquent
 */
class Tag extends Model
{
    use HasGenericModelRelation;

    protected $visible = ['id', 'name', 'color'];

    public function tagCategory(): BelongsTo
    {
        return $this->belongsTo(TagCategory::class);
    }

    /**
     * @return Builder
     */
    public function scopeUnique(Builder $query, ?int $categoryId = null)
    {
        if ($categoryId !== null) {
            $query = $query->where('tag_category_id', $categoryId);
        }

        return $query->groupBy('name');
    }

    public function getUsage(): Collection
    {
        $result = new Collection();
        $result = match ($this->tagCategory->name) {
            TagCategory::DUNGEON_ROUTE_PERSONAL, TagCategory::DUNGEON_ROUTE_TEAM => DungeonRoute::join('tags', 'tags.model_id', '=', 'dungeon_routes.id')
                ->where('tags.model_class', $this->model_class)
                ->where('tags.name', $this->name)
                ->where('tags.user_id', $this->user_id)
                ->where('tags.tag_category_id', $this->tag_category_id)
                ->get(),
            default => $result,
        };

        return $result;
    }

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
