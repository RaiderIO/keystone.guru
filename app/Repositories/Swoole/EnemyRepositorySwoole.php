<?php

namespace App\Repositories\Swoole;

use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\EnemyRepository;
use App\Repositories\Swoole\Interfaces\EnemyRepositorySwooleInterface;
use App\Repositories\Swoole\Traits\ClonesCollections;
use Illuminate\Support\Collection;
use Override;

/**
 * @method Enemy                  create(array<string, mixed> $attributes)
 * @method Enemy|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Enemy                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Enemy                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                   save(Enemy $model)
 * @method bool                   update(Enemy $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                   delete(Enemy $model)
 * @method Collection<int, Enemy> all()
 */
class EnemyRepositorySwoole extends EnemyRepository implements EnemyRepositorySwooleInterface
{
    use ClonesCollections;

    /**
     * @var Collection<int, Collection<int, Enemy>>
     */
    private Collection $availableEnemiesByMappingVersion;

    public function __construct()
    {
        parent::__construct();

        $this->availableEnemiesByMappingVersion = collect();
    }

    /**
     * @inheritDoc
     * @return Collection<int, Enemy>
     */
    #[Override]
    public function getAvailableEnemiesForDungeonRouteBuilder(MappingVersion $mappingVersion): Collection
    {
        if (!$this->availableEnemiesByMappingVersion->has($mappingVersion->id)) {
            $availableEnemies = parent::getAvailableEnemiesForDungeonRouteBuilder($mappingVersion);

            $this->availableEnemiesByMappingVersion->put($mappingVersion->id, $availableEnemies);
        }

        $availableEnemies = $this->availableEnemiesByMappingVersion->get($mappingVersion->id);

        return $this->cloneCollection($availableEnemies);
    }
}
