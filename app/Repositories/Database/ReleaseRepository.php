<?php

namespace App\Repositories\Database;

use App\Models\Release;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;

class ReleaseRepository extends DatabaseRepository implements ReleaseRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Release::class);
    }
}
