<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcBolsteringWhitelist;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcBolsteringWhitelist                  create(array<string, mixed> $attributes)
 * @method NpcBolsteringWhitelist|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcBolsteringWhitelist                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcBolsteringWhitelist                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(NpcBolsteringWhitelist $model)
 * @method bool                                    update(NpcBolsteringWhitelist $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(NpcBolsteringWhitelist $model)
 * @method Collection<int, NpcBolsteringWhitelist> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface NpcBolsteringWhitelistRepositoryInterface extends BaseRepositoryInterface
{
}
