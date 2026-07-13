<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonUserLink;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonUserLink                  create(array<string, mixed> $attributes)
 * @method PatreonUserLink|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonUserLink                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonUserLink                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                             save(PatreonUserLink $model)
 * @method bool                             update(PatreonUserLink $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                             delete(PatreonUserLink $model)
 * @method Collection<int, PatreonUserLink> all()
 * @method bool                             exists(array<int, string> $columns)
 */
interface PatreonUserLinkRepositoryInterface extends BaseRepositoryInterface
{
}
