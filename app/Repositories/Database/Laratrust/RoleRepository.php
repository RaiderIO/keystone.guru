<?php

namespace App\Repositories\Database\Laratrust;

use App\Models\Laratrust\Role;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Laratrust\RoleRepositoryInterface;

class RoleRepository extends DatabaseRepository implements RoleRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Role::class);
    }
}
