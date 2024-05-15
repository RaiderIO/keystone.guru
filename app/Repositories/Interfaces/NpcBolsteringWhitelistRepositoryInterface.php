<?php

namespace App\Repositories\Interfaces;

use App\Models\NpcBolsteringWhitelist;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcBolsteringWhitelist create(array $attributes)
 * @method NpcBolsteringWhitelist|null find(int $id, array|string $columns = ['*'])
 * @method NpcBolsteringWhitelist findOrFail(int $id, array|string $columns = ['*'])
 * @method NpcBolsteringWhitelist findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(NpcBolsteringWhitelist $model)
 * @method bool update(NpcBolsteringWhitelist $model, array $attributes = [], array $options = [])
 * @method bool delete(NpcBolsteringWhitelist $model)
 * @method Collection<NpcBolsteringWhitelist> all()
 */
interface NpcBolsteringWhitelistRepositoryInterface extends BaseRepositoryInterface
{

}
