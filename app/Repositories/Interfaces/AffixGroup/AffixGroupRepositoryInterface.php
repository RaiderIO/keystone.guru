<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroup;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroup                  create(array<string, mixed> $attributes)
 * @method AffixGroup|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroup                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroup                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                        save(AffixGroup $model)
 * @method bool                        update(AffixGroup $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                        delete(AffixGroup $model)
 * @method Collection<int, AffixGroup> all()
 * @method bool                        exists(array<string, mixed> $columns)
 */
interface AffixGroupRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, AffixGroup>
     */
    public function getBySeasonId(int $id): Collection;
}
