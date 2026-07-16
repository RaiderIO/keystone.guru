<?php

namespace App\Repositories\Database;

use App\Models\BannedIpAddress;
use App\Repositories\Interfaces\BannedIpAddressRepositoryInterface;

class BannedIpAddressRepository extends DatabaseRepository implements BannedIpAddressRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(BannedIpAddress::class);
    }
}
