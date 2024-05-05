<?php

namespace App\Repositories\Database;

use App\Models\RouteAttribute;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\RouteAttributeRepositoryInterface;

class RouteAttributeRepository extends DatabaseRepository implements RouteAttributeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(RouteAttribute::class);
    }
}
