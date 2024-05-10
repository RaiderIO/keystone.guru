<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonBenefit;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonBenefit create(array $attributes)
 * @method PatreonBenefit find(int $id, array $columns = [])
 * @method PatreonBenefit findOrFail(int $id, array $columns = [])
 * @method PatreonBenefit findOrNew(int $id, array $columns = [])
 * @method bool save(PatreonBenefit $model)
 * @method bool update(PatreonBenefit $model, array $attributes = [], array $options = [])
 * @method bool delete(PatreonBenefit $model)
 * @method Collection<PatreonBenefit> all()
 */
interface PatreonBenefitRepositoryInterface extends BaseRepositoryInterface
{

}
