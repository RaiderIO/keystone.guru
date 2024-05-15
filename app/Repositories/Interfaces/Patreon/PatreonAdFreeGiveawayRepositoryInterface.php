<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonAdFreeGiveaway create(array $attributes)
 * @method PatreonAdFreeGiveaway|null find(int $id, array|string $columns = ['*'])
 * @method PatreonAdFreeGiveaway findOrFail(int $id, array|string $columns = ['*'])
 * @method PatreonAdFreeGiveaway findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(PatreonAdFreeGiveaway $model)
 * @method bool update(PatreonAdFreeGiveaway $model, array $attributes = [], array $options = [])
 * @method bool delete(PatreonAdFreeGiveaway $model)
 * @method Collection<PatreonAdFreeGiveaway> all()
 */
interface PatreonAdFreeGiveawayRepositoryInterface extends BaseRepositoryInterface
{

}
