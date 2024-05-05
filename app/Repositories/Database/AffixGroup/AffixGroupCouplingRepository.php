<?php

namespace App\Repositories\Database\AffixGroup;

use App\Models\AffixGroup\AffixGroupCoupling;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\AffixGroup\AffixGroupCouplingRepositoryInterface;

class AffixGroupCouplingRepository extends DatabaseRepository implements AffixGroupCouplingRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(AffixGroupCoupling::class);
    }
}
