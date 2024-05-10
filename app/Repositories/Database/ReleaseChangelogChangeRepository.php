<?php

namespace App\Repositories\Database;

use App\Models\ReleaseChangelogChange;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\ReleaseChangelogChangeRepositoryInterface;

class ReleaseChangelogChangeRepository extends DatabaseRepository implements ReleaseChangelogChangeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ReleaseChangelogChange::class);
    }
}
