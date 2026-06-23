<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonBenefit                  create(array<string, mixed> $attributes)
 * @method PatreonBenefit|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonBenefit                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonBenefit                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(PatreonBenefit $model)
 * @method bool                            update(PatreonBenefit $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(PatreonBenefit $model)
 * @method Collection<int, PatreonBenefit> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface PatreonBenefitRepositoryInterface extends BaseRepositoryInterface
{
}
