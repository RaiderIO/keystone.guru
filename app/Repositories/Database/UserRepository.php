<?php

namespace App\Repositories\Database;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository extends DatabaseRepository implements UserRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(User::class);
    }
}
