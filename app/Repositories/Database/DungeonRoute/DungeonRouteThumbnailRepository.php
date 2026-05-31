<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteThumbnailRepositoryInterface;

class DungeonRouteThumbnailRepository extends DatabaseRepository implements DungeonRouteThumbnailRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteThumbnail::class);
    }
}
