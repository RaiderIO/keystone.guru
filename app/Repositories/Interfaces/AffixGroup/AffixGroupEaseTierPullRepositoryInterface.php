<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroupEaseTierPull             create(array $attributes)
 * @method AffixGroupEaseTierPull|null        find(int $id, array|string $columns = ['*'])
 * @method AffixGroupEaseTierPull             findOrFail(int $id, array|string $columns = ['*'])
 * @method AffixGroupEaseTierPull             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                               save(AffixGroupEaseTierPull $model)
 * @method bool                               update(AffixGroupEaseTierPull $model, array $attributes = [], array $options = [])
 * @method bool                               delete(AffixGroupEaseTierPull $model)
 * @method Collection<AffixGroupEaseTierPull> all()
 */
interface AffixGroupEaseTierPullRepositoryInterface extends BaseRepositoryInterface
{
}
