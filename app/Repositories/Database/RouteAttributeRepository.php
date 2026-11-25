<?php

namespace App\Repositories\Database;

use App\Models\RouteAttribute;
use App\Repositories\Interfaces\RouteAttributeRepositoryInterface;
use Illuminate\Support\Collection;

class RouteAttributeRepository extends DatabaseRepository implements RouteAttributeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(RouteAttribute::class);
    }

    /**
     * @return Collection<int, RouteAttribute>
     */
    public function getAllOrderedById(): Collection
    {
        return RouteAttribute::query()->orderBy('id')->get();
    }
}
