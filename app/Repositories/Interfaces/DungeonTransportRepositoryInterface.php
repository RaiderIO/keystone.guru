<?php

namespace App\Repositories\Interfaces;

use App\Models\DungeonTransport;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonTransport                  create(array<string, mixed> $attributes)
 * @method DungeonTransport|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonTransport                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonTransport                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(DungeonTransport $model)
 * @method bool                              update(DungeonTransport $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(DungeonTransport $model)
 * @method Collection<int, DungeonTransport> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface DungeonTransportRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Clears the link of every transport that points at the given transport.
     *
     * @return int The number of transports that were unlinked.
     */
    public function unlinkTransportsLinkedTo(int $dungeonTransportId): int;
}
