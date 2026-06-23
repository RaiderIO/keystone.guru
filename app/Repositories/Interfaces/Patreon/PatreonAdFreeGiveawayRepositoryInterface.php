<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonAdFreeGiveaway                  create(array<string, mixed> $attributes)
 * @method PatreonAdFreeGiveaway|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonAdFreeGiveaway                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonAdFreeGiveaway                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                   save(PatreonAdFreeGiveaway $model)
 * @method bool                                   update(PatreonAdFreeGiveaway $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                   delete(PatreonAdFreeGiveaway $model)
 * @method Collection<int, PatreonAdFreeGiveaway> all()
 * @method bool                                   exists(array<int, string> $columns)
 */
interface PatreonAdFreeGiveawayRepositoryInterface extends BaseRepositoryInterface
{
}
