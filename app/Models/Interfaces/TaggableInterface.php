<?php

namespace App\Models\Interfaces;

use App\Models\Tags\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface TaggableInterface
{
    /** @return HasMany<Tag, Model> */
    public function tags(?int $tagCategoryId = null): HasMany;

    public function hasTag(int $tagCategoryId, string $name): bool;
}
