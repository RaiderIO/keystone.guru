<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonUserBenefit;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonUserBenefit                  create(array<string, mixed> $attributes)
 * @method PatreonUserBenefit|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonUserBenefit                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonUserBenefit                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                save(PatreonUserBenefit $model)
 * @method bool                                update(PatreonUserBenefit $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                delete(PatreonUserBenefit $model)
 * @method Collection<int, PatreonUserBenefit> all()
 * @method bool                                exists(array<int, string> $columns)
 */
interface PatreonUserBenefitRepositoryInterface extends BaseRepositoryInterface
{
}
