<?php

namespace App\Repositories\Database\Mapping;

use App\Models\Mapping\MappingCommitLog;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Mapping\MappingCommitLogRepositoryInterface;

class MappingCommitLogRepository extends DatabaseRepository implements MappingCommitLogRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MappingCommitLog::class);
    }
}
