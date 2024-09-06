<?php

namespace App\Repositories\Database\AffixGroup;

use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\AffixGroup\AffixGroupEaseTierRepositoryInterface;

class AffixGroupEaseTierRepository extends DatabaseRepository implements AffixGroupEaseTierRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(AffixGroupEaseTier::class);
    }
}
