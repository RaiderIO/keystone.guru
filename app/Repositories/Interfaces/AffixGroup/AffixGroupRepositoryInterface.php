<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroup create(array $attributes)
 * @method AffixGroup find(int $id, array $columns = [])
 * @method AffixGroup findOrFail(int $id, array $columns = [])
 * @method AffixGroup findOrNew(int $id, array $columns = [])
 * @method bool save(AffixGroup $model)
 * @method bool update(AffixGroup $model, array $attributes = [], array $options = [])
 * @method bool delete(AffixGroup $model)
 * @method Collection<AffixGroup> all()
 */
interface AffixGroupRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<AffixGroup>
     */
    public function getBySeasonId(int $id): Collection;
}
