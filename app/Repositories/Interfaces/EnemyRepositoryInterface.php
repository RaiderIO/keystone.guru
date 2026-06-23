<?php

namespace App\Repositories\Interfaces;

use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Enemy                  create(array<string, mixed> $attributes)
 * @method Enemy|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Enemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Enemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                   save(Enemy $model)
 * @method bool                   update(Enemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                   delete(Enemy $model)
 * @method Collection<int, Enemy> all()
 * @method bool                   exists(array<string, mixed> $columns)
 */
interface EnemyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param  MappingVersion         $mappingVersion
     * @return Collection<int, Enemy>
     */
    public function getAvailableEnemiesForDungeonRouteBuilder(MappingVersion $mappingVersion): Collection;
}
