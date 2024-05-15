<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonUserLink;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonUserLink create(array $attributes)
 * @method PatreonUserLink|null find(int $id, array|string $columns = ['*'])
 * @method PatreonUserLink findOrFail(int $id, array|string $columns = ['*'])
 * @method PatreonUserLink findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(PatreonUserLink $model)
 * @method bool update(PatreonUserLink $model, array $attributes = [], array $options = [])
 * @method bool delete(PatreonUserLink $model)
 * @method Collection<PatreonUserLink> all()
 */
interface PatreonUserLinkRepositoryInterface extends BaseRepositoryInterface
{

}
