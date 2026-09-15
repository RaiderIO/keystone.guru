<?php

namespace App\Repositories\Database\Patreon;

use App\Models\Patreon\PatreonSyncRun;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Patreon\PatreonSyncRunRepositoryInterface;
use Illuminate\Support\Collection;

class PatreonSyncRunRepository extends DatabaseRepository implements PatreonSyncRunRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PatreonSyncRun::class);
    }

    public function getMostRecent(int $limit): Collection
    {
        return PatreonSyncRun::query()
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
