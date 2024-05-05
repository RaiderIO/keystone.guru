<?php

namespace App\Repositories\Database;

use App\Models\Path;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\PathRepositoryInterface;

class PathRepository extends DatabaseRepository implements PathRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Path::class);
    }
}
