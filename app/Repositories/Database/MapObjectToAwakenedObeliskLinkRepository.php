<?php

namespace App\Repositories\Database;

use App\Models\MapObjectToAwakenedObeliskLink;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\MapObjectToAwakenedObeliskLinkRepositoryInterface;

class MapObjectToAwakenedObeliskLinkRepository extends DatabaseRepository implements MapObjectToAwakenedObeliskLinkRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MapObjectToAwakenedObeliskLink::class);
    }
}
