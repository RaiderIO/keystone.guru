<?php

namespace App\Models\Traits;

use App\Models\Tags\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $model_class
 * @property Collection|Tag[] $tags
 *
 * @mixin Model
 */
trait HasTags
{
    public function tags(?int $tagCategoryId = null): HasMany
    {
        $result = $this->hasMany(Tag::class, 'model_id')->where('model_class', $this::class);

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
