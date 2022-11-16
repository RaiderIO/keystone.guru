<?php

namespace App\Models\Traits;

use App\Models\Tags\Tag;
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
     * @param int|null $tagCategoryId
     * @return hasMany
     */
    public function tags(?int $tagCategoryId = null): HasMany
    {
        $result = $this->hasMany(Tag::class, 'model_id')->where('model_class', get_class($this));

        if ($tagCategoryId !== null) {
            $result->where('tag_category_id', $tagCategoryId);
        }

        return $result;
    }

    /**
     * @param int $tagCategoryId
     * @param string $name
     * @return bool
     */
    public function hasTag(int $tagCategoryId, string $name): bool
    {
        return in_array($name, $this->tags($tagCategoryId)->get()->pluck(['name'])->toArray());
    }
}
