<?php

namespace App\Repositories\Database;

use App\Models\ReleaseChangelog;
use App\Repositories\Interfaces\ReleaseChangelogRepositoryInterface;

class ReleaseChangelogRepository extends DatabaseRepository implements ReleaseChangelogRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ReleaseChangelog::class);
    }
}
