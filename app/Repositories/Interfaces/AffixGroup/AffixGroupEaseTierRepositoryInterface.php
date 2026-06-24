<?php

namespace App\Repositories\Interfaces\AffixGroup;

use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method AffixGroupEaseTier                  create(array<string, mixed> $attributes)
 * @method AffixGroupEaseTier|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroupEaseTier                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method AffixGroupEaseTier                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                save(AffixGroupEaseTier $model)
 * @method bool                                update(AffixGroupEaseTier $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                delete(AffixGroupEaseTier $model)
 * @method Collection<int, AffixGroupEaseTier> all()
 * @method bool                                exists(array<int, string> $columns)
 */
interface AffixGroupEaseTierRepositoryInterface extends BaseRepositoryInterface
{
}
