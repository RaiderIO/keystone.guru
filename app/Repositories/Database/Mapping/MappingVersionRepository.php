<?php

namespace App\Repositories\Database\Mapping;

use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Mapping\MappingVersionRepositoryInterface;

class MappingVersionRepository extends DatabaseRepository implements MappingVersionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MappingVersion::class);
    }
}
