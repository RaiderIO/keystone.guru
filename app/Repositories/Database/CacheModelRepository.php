<?php

namespace App\Repositories\Database;

use App\Models\CacheModel;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CacheModelRepositoryInterface;

class CacheModelRepository extends DatabaseRepository implements CacheModelRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CacheModel::class);
    }
}
