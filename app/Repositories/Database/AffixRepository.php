<?php

namespace App\Repositories\Database;

use App\Models\Affix;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\AffixRepositoryInterface;

class AffixRepository extends DatabaseRepository implements AffixRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Affix::class);
    }
}
