<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroup             create(array $attributes)
 * @method AffixGroup|null        find(int $id, array|string $columns = ['*'])
 * @method AffixGroup             findOrFail(int $id, array|string $columns = ['*'])
 * @method AffixGroup             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                   save(AffixGroup $model)
 * @method bool                   update(AffixGroup $model, array $attributes = [], array $options = [])
 * @method bool                   delete(AffixGroup $model)
 * @method Collection<AffixGroup> all()
 */
interface AffixGroupRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<AffixGroup>
     */
    public function getBySeasonId(int $id): Collection;
}
