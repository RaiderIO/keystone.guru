<?php

namespace App\Models\Interfaces;

use App\Models\Tags\Tag;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

interface HasTagsInterface
{
    /** @return HasMany<Tag, \Illuminate\Database\Eloquent\Model> */
    public function tags(?int $categoryId = null): HasMany;

    /** @return Collection<int, string> */
    public function getUniqueTagNames(?int $categoryId = null): Collection;

    public function getUsageCountByName(string $tagName): int;

    public function hasTag(int $tagCategoryId, string $name): bool;
}
