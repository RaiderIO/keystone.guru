<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroupCoupling;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroupCoupling                  create(array<string, mixed> $attributes)
 * @method AffixGroupCoupling|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroupCoupling                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroupCoupling                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                save(AffixGroupCoupling $model)
 * @method bool                                update(AffixGroupCoupling $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                delete(AffixGroupCoupling $model)
 * @method Collection<int, AffixGroupCoupling> all()
 * @method bool                                exists(array<int, string> $columns)
 */
interface AffixGroupCouplingRepositoryInterface extends BaseRepositoryInterface
{
}
