<?php

namespace App\Repositories\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroup create(array $attributes)
 * @method AffixGroup find(int $id)
 * @method bool save(AffixGroup $AffixGroup)
 */
interface AffixGroupRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<AffixGroup>
     */
    public function getBySeasonId(int $id): Collection;
}
