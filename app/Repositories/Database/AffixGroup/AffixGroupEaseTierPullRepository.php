<?php

namespace App\Repositories\Database\AffixGroup;

use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\AffixGroup\AffixGroupEaseTierPullRepositoryInterface;

class AffixGroupEaseTierPullRepository extends DatabaseRepository implements AffixGroupEaseTierPullRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(AffixGroupEaseTierPull::class);
    }
}
