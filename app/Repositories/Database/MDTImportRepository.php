<?php

namespace App\Repositories\Database;

use App\Models\MDTImport;
use App\Repositories\Interfaces\MDTImportRepositoryInterface;

class MDTImportRepository extends DatabaseRepository implements MDTImportRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MDTImport::class);
    }
}
