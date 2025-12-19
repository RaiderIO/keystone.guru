<?php

namespace App\Models\Traits;

use App\Models\Tags\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * This model manages tags for other objects. I.e., a user can have tags for a dungeon route.
 * The user will have the HasTags trait, and the dungeon route will have the Taggable trait.
 *
 * @property int             $context_id
 * @property string          $context_class
 * @property Collection<Tag> $tags
 *
 * @mixin Model
 */
trait HasTags
{
    /**
     * @return HasMany<Tag>
     */
    public function tags(?int $categoryId = null): HasMany
    {
        $result = $this->hasMany(Tag::class, 'context_id')
            ->where('context_class', static::class);

        if ($categoryId !== null) {
            $result->where('tag_category_id', $categoryId);
        }

        return $result;
    }

    /**
     * @return Collection<string>
     */
    public function getUniqueTagNames(?int $categoryId = null): Collection
    {
        $builder = $this->tags();

        if ($categoryId !== null) {
            $builder->where('tag_category_id', $categoryId);
        }

        return $builder->select('name')
            ->distinct()
            ->get()
            ->pluck('name');
    }

    /**
     * @param  string $tagName
     * @return int
     */
    public function getUsageCountByName(string $tagName): int
    {
        return $this->tags()->where('name', $tagName)->whereNotNull('model_id')->count();
    }

    public function hasTag(int $tagCategoryId, string $name): bool
    {
        return in_array($name, $this->tags($tagCategoryId)->get()->pluck(['name'])->toArray());
    }
}
