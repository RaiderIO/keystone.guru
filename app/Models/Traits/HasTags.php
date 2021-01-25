<?php

namespace App\Models\Traits;

use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $model_class
 *
 * @property Collection|Tag[] $tags
 *
 * @mixin Model
 */
trait HasTags
{
    /**
     * @param TagCategory|null $category
     * @return hasMany
     */
    public function tags(?TagCategory $category = null): HasMany
    {
        $result = $this->hasMany('\App\Models\Tags\Tag', 'model_id')->where('model_class', get_class($this));

        if ($category !== null) {
            $result->where('tag_category_id', $category->id);
        }

        return $result;
    }

    /**
     * @param TagCategory $tagCategory
     * @param string $name
     * @return bool
     */
    public function hasTag(TagCategory $tagCategory, string $name): bool
    {
        return in_array($name, $this->tags($tagCategory)->get()->pluck(['name'])->toArray());
    }
}