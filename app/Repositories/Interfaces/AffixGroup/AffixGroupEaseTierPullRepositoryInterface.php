<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroupEaseTierPull                  create(array<string, mixed> $attributes)
 * @method AffixGroupEaseTierPull|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroupEaseTierPull                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroupEaseTierPull                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(AffixGroupEaseTierPull $model)
 * @method bool                                    update(AffixGroupEaseTierPull $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(AffixGroupEaseTierPull $model)
 * @method Collection<int, AffixGroupEaseTierPull> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface AffixGroupEaseTierPullRepositoryInterface extends BaseRepositoryInterface
{
}
