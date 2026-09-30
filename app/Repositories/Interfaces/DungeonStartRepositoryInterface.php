<?php

namespace App\Repositories\Interfaces;

use App\Models\DungeonStart;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonStart                  create(array<string, mixed> $attributes)
 * @method DungeonStart|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonStart                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method DungeonStart                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                          save(DungeonStart $model)
 * @method bool                          update(DungeonStart $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                          delete(DungeonStart $model)
 * @method Collection<int, DungeonStart> all()
 * @method bool                          exists(array<int, string> $columns)
 */
interface DungeonStartRepositoryInterface extends BaseRepositoryInterface
{
    public function isDungeonStartOfMappingVersion(int $id, int $mappingVersionId): bool;

    /**
     * The dungeon start in the new mapping version with the same comment as the given start, or null when the
     * start has no comment or no such start exists.
     */
    public function findMatchingDungeonStartIdInMappingVersion(int $dungeonStartId, int $mappingVersionId): ?int;
}
