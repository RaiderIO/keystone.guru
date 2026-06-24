<?php

namespace App\Repositories\Interfaces;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Dungeon                  create(array<string, mixed> $attributes)
 * @method Dungeon|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Dungeon                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Dungeon                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                     save(Dungeon $model)
 * @method bool                     update(Dungeon $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                     delete(Dungeon $model)
 * @method Collection<int, Dungeon> all()
 * @method bool                     exists(array<string, mixed> $columns)
 */
interface DungeonRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, mixed>
     */
    public function getAllMapIds(): Collection;

    public function getByChallengeModeIdOrFail(int $challengeModeId): Dungeon;

    public function getMappingVersionByVersion(Dungeon $dungeon, int $version): ?MappingVersion;

    public function getByInstanceId(int $instanceId): ?Dungeon;

    public function getByMappingVersion(int $challengeModeId, ?int $mappingVersion): ?Dungeon;
}
