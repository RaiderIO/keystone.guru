<?php

namespace App\Repositories\Interfaces;

use App\Models\NpcBolsteringWhitelist;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcBolsteringWhitelist create(array $attributes)
 * @method NpcBolsteringWhitelist find(int $id, array $columns = [])
 * @method NpcBolsteringWhitelist findOrFail(int $id, array $columns = [])
 * @method NpcBolsteringWhitelist findOrNew(int $id, array $columns = [])
 * @method bool save(NpcBolsteringWhitelist $model)
 * @method bool update(NpcBolsteringWhitelist $model, array $attributes = [], array $options = [])
 * @method bool delete(NpcBolsteringWhitelist $model)
 * @method Collection<NpcBolsteringWhitelist> all()
 */
interface NpcBolsteringWhitelistRepositoryInterface extends BaseRepositoryInterface
{

}
