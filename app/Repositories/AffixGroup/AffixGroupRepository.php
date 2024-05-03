<?php

namespace App\Repositories\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class AffixGroupRepository extends BaseRepository implements AffixGroupRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(AffixGroup::class);
    }

    public function getBySeasonId(int $id): Collection
    {
        return AffixGroup::where('season_id', $id)->get();
    }
}
