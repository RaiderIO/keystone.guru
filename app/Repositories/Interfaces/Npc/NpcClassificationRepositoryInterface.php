<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcClassification;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcClassification                  create(array<string, mixed> $attributes)
 * @method NpcClassification|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcClassification                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcClassification                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                               save(NpcClassification $model)
 * @method bool                               update(NpcClassification $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                               delete(NpcClassification $model)
 * @method Collection<int, NpcClassification> all()
 * @method bool                               exists(array<int, string> $columns)
 */
interface NpcClassificationRepositoryInterface extends BaseRepositoryInterface
{
}
