<?php

namespace App\Repositories\Stub\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Stub\StubRepository;
use Illuminate\Support\Collection;

class AffixGroupRepository extends StubRepository implements AffixGroupRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(AffixGroup::class);
    }

    public function getBySeasonId(int $id): Collection
    {
        return collect();
    }
}
