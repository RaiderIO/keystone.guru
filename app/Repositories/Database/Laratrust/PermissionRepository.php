<?php

namespace App\Repositories\Database\Laratrust;

use App\Models\Laratrust\Permission;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Laratrust\PermissionRepositoryInterface;

class PermissionRepository extends DatabaseRepository implements PermissionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Permission::class);
    }
}
