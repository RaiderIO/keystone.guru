<?php

namespace App\Repositories\Database;

use App\Models\UserReport;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\UserReportRepositoryInterface;

class UserReportRepository extends DatabaseRepository implements UserReportRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(UserReport::class);
    }
}
