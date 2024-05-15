<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonAdFreeGiveaway create(array $attributes)
 * @method PatreonAdFreeGiveaway find(int $id, array $columns = [])
 * @method PatreonAdFreeGiveaway findOrFail(int $id, array $columns = [])
 * @method PatreonAdFreeGiveaway findOrNew(int $id, array $columns = [])
 * @method bool save(PatreonAdFreeGiveaway $model)
 * @method bool update(PatreonAdFreeGiveaway $model, array $attributes = [], array $options = [])
 * @method bool delete(PatreonAdFreeGiveaway $model)
 * @method Collection<PatreonAdFreeGiveaway> all()
 */
interface PatreonAdFreeGiveawayRepositoryInterface extends BaseRepositoryInterface
{

}
