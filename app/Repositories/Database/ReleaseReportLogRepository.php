<?php

namespace App\Repositories\Database;

use App\Models\ReleaseReportLog;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\ReleaseReportLogRepositoryInterface;

class ReleaseReportLogRepository extends DatabaseRepository implements ReleaseReportLogRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ReleaseReportLog::class);
    }
}
