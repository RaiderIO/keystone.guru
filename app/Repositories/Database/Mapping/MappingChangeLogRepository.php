<?php

namespace App\Repositories\Database\Mapping;

use App\Models\Mapping\MappingChangeLog;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Mapping\MappingChangeLogRepositoryInterface;

class MappingChangeLogRepository extends DatabaseRepository implements MappingChangeLogRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MappingChangeLog::class);
    }
}
