<?php

namespace App\Repositories\Database;

use App\Models\UserIpAddress;
use App\Repositories\Interfaces\UserIpAddressRepositoryInterface;

class UserIpAddressRepository extends DatabaseRepository implements UserIpAddressRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(UserIpAddress::class);
    }
}
