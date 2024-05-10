<?php

namespace App\Repositories\Database\AffixGroup;

use App\Models\AffixGroup\AffixGroupBase;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\AffixGroup\AffixGroupBaseRepositoryInterface;

class AffixGroupBaseRepository extends DatabaseRepository implements AffixGroupBaseRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(AffixGroupBase::class);
    }
}
