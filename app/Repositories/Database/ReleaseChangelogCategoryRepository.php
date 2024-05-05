<?php

namespace App\Repositories\Database;

use App\Models\ReleaseChangelogCategory;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\ReleaseChangelogCategoryRepositoryInterface;

class ReleaseChangelogCategoryRepository extends DatabaseRepository implements ReleaseChangelogCategoryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ReleaseChangelogCategory::class);
    }
}
