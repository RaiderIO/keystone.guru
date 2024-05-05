<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonUserBenefit;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonUserBenefit create(array $attributes)
 * @method PatreonUserBenefit find(int $id, array $columns = [])
 * @method PatreonUserBenefit findOrFail(int $id, array $columns = [])
 * @method PatreonUserBenefit findOrNew(int $id, array $columns = [])
 * @method bool save(PatreonUserBenefit $model)
 * @method bool update(PatreonUserBenefit $model, array $attributes = [], array $options = [])
 * @method bool delete(PatreonUserBenefit $model)
 * @method Collection<PatreonUserBenefit> all()
 */
interface PatreonUserBenefitRepositoryInterface extends BaseRepositoryInterface
{

}
