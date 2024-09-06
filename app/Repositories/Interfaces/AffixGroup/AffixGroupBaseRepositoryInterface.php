<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroupBase;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroupBase create(array $attributes)
 * @method AffixGroupBase|null find(int $id, array|string $columns = ['*'])
 * @method AffixGroupBase findOrFail(int $id, array|string $columns = ['*'])
 * @method AffixGroupBase findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(AffixGroupBase $model)
 * @method bool update(AffixGroupBase $model, array $attributes = [], array $options = [])
 * @method bool delete(AffixGroupBase $model)
 * @method Collection<AffixGroupBase> all()
 */
interface AffixGroupBaseRepositoryInterface extends BaseRepositoryInterface
{

}
