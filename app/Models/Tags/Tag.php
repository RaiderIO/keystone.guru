<?php

namespace App\Models\Tags;

use App\Http\Requests\Tag\TagFormRequest;
use App\Models\Interfaces\HasTagsInterface;
use App\Models\Traits\HasGenericModelRelation;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A label a user (or a team) puts on their own content to find it back: never published, unordered, unbounded in
 * number, and the thing every "my content" list filters on. The counterpart is DungeonRouteCollection, which
 * publishes an ordered set under its own URL - tags organize, collections publish, and neither replaces the other.
 *
 * The context_class/model_class pair makes any model taggable, but a model only earns tags once the number a single
 * user can own is unbounded; a model capped at a handful of rows is organized by its own fields instead.
 *
 * @property int         $id
 * @property int         $context_id
 * @property string      $context_class
 * @property int         $tag_category_id
 * @property int|null    $model_id
 * @property string|null $model_class
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

    /**
     * @return BelongsTo<TagCategory, $this>
     */
    public function tagCategory(): BelongsTo
    {
        return $this->belongsTo(TagCategory::class);
    }

    /**
     * @param  Builder<Tag> $query
     * @return Builder<Tag>
     */
    #[Scope]
    protected function unique(Builder $query, ?int $categoryId = null): Builder
    {
        if ($categoryId !== null) {
            $query = $query->where('tag_category_id', $categoryId);
        }

        return $query->groupBy('name');
    }

    public static function saveFromRequest(TagFormRequest $request, Model $context, int $tagCategoryId): Tag
    {
        /** @var Model&HasTagsInterface $context */
        $validated = $request->validated();

        return Tag::create([
            'context_id'      => $context->getKey(),
            'context_class'   => $context::class,
            'tag_category_id' => $tagCategoryId,
            'model_id'        => null,
            'model_class'     => null,
            'name'            => $validated['tag_name_new'],
            'color'           => null,
        ]);
    }
}
