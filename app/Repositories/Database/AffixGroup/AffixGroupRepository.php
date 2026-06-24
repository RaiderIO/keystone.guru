<?php

namespace App\Repositories\Database\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use Illuminate\Support\Collection;

class AffixGroupRepository extends DatabaseRepository implements AffixGroupRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(AffixGroup::class);
    }
    /**
     * @return Collection<int, AffixGroup>
     */
    public function getBySeasonId(int $id): Collection
    {
        return AffixGroup::where('season_id', $id)->get();
    }
}
