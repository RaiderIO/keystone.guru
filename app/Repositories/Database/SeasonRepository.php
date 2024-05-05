<?php

namespace App\Repositories\Database;

use App\Models\Season;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\SeasonRepositoryInterface;

class SeasonRepository extends DatabaseRepository implements SeasonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Season::class);
    }
}
