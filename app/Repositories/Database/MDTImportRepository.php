<?php

namespace App\Repositories\Database;

use App\Models\MDTImport;
use App\Repositories\Interfaces\MDTImportRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MDTImportRepository extends DatabaseRepository implements MDTImportRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MDTImport::class);
    }

    /**
     * @return LengthAwarePaginator<int, MDTImport>
     */
    public function paginateFailedNewestFirst(int $perPage): LengthAwarePaginator
    {
        return MDTImport::whereNotNull('error')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
