<?php

namespace App\Repositories\Database;

use App\Models\ReleaseReportLog;
use App\Repositories\Interfaces\ReleaseReportLogRepositoryInterface;

class ReleaseReportLogRepository extends DatabaseRepository implements ReleaseReportLogRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ReleaseReportLog::class);
    }

    public function hasReportedVersionOnPlatform(string $version, string $platform): bool
    {
        return ReleaseReportLog::where('version', $version)
            ->where('platform', $platform)
            ->exists();
    }
}
