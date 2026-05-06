<?php

namespace App\Models\Tags;

use App\Http\Requests\Tag\TagFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Traits\HasGenericModelRelation;
use App\Models\Traits\HasTags;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int         $id
 * @property int         $context_id
 * @property string      $context_class
 * @property int         $tag_category_id
 * @property int         $model_id
 * @property string      $model_class
 * @property string      $name
 * @property string|null $color
 *
 * @property Carbon      $updated_at
 * @property Carbon      $created_at
 * @property TagCategory $tagCategory
 *
 * @mixin Eloquent
 */
class Tag extends Model
{
    use HasGenericModelRelation;

    protected $fillable = [
        'context_id',
        'context_class',
        'tag_category_id',
        'model_id',
        'model_class',
        'name',
        'color',
    ];

    protected $visible = [
        'id',
        'name',
        'color',
    ];

    protected $with = ['tagCategory'];

    public function tagCategory(): BelongsTo
    {
        return $this->belongsTo(TagCategory::class);
    }

    #[Scope]
    protected function unique(Builder $query, ?int $categoryId = null): Builder
    {
        if ($categoryId !== null) {
            $query = $query->where('tag_category_id', $categoryId);
        }

        return $query->groupBy('name');
    }

    public function getUsageByName(): Collection
    {
        return match ($this->tagCategory->name) {
            TagCategory::DUNGEON_ROUTE_PERSONAL, TagCategory::DUNGEON_ROUTE_TEAM => DungeonRoute::join('tags', 'tags.model_id', '=', 'dungeon_routes.id')
                ->where('tags.model_class', $this->model_class)
                ->where('tags.name', $this->name)
                ->where('tags.context_id', $this->context_id)
                ->where('tags.context_class', $this->context_class)
                ->where('tags.tag_category_id', $this->tag_category_id)
                ->get(),
            default => collect(),
        };
    }

    public static function saveFromRequest(TagFormRequest $request, Model $context, int $tagCategoryId): Tag
    {
        /** @var Model|HasTags $context */
        $validated = $request->validated();

        return Tag::create([
            'context_id'      => $context->id,
            'context_class'   => $context::class,
            'tag_category_id' => $tagCategoryId,
            'model_id'        => null,
            'model_class'     => null,
            'name'            => $validated['tag_name_new'],
            'color'           => null,
        ]);
    }
}
