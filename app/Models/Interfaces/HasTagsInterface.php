<?php

namespace App\Models\Interfaces;

use App\Models\Tags\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasTagsInterface
{
    /** @return HasMany<Tag, Model> */
    public function tags(?int $categoryId = null): HasMany;

    public function getUsageCountByName(string $tagName): int;

    public function hasTag(int $tagCategoryId, string $name): bool;
}
