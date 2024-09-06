<?php

namespace App\Repositories\Database\Tags;

use App\Models\Tags\TagCategory;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Tags\TagCategoryRepositoryInterface;

class TagCategoryRepository extends DatabaseRepository implements TagCategoryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(TagCategory::class);
    }
}
