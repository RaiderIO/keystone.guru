<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroupBase;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroupBase                  create(array<string, mixed> $attributes)
 * @method AffixGroupBase|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroupBase                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroupBase                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(AffixGroupBase $model)
 * @method bool                            update(AffixGroupBase $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(AffixGroupBase $model)
 * @method Collection<int, AffixGroupBase> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface AffixGroupBaseRepositoryInterface extends BaseRepositoryInterface
{
}
