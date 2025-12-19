<?php

namespace App\Models\Traits;

use App\Models\Tags\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * This model can be tagged by another object. I.e., a user can have tags for a dungeon route.
 * The user will have the HasTags trait, and the dungeon route will have the Taggable trait.
 *
 * @property int             $model_id
 * @property string          $model_class
 * @property Collection<Tag> $tags
 *
 * @mixin Model
 */
trait Taggable
{
    public function tags(?int $tagCategoryId = null): HasMany
    {
        $result = $this->hasMany(Tag::class, 'model_id')
            ->where('model_class', $this::class);

        if ($tagCategoryId !== null) {
            $result->where('tag_category_id', $tagCategoryId);
        }

        return $result;
    }

    public function hasTag(int $tagCategoryId, string $name): bool
    {
        return in_array($name, $this->tags($tagCategoryId)->get()->pluck(['name'])->toArray());
    }
}
