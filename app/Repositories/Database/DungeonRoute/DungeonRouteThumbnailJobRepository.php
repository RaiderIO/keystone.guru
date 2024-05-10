<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteThumbnailJobRepositoryInterface;

class DungeonRouteThumbnailJobRepository extends DatabaseRepository implements DungeonRouteThumbnailJobRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteThumbnailJob::class);
    }
}
