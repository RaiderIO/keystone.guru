<?php

namespace App\Repositories\Swoole;

use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\EnemyRepository;
use App\Repositories\Swoole\Interfaces\EnemyRepositorySwooleInterface;
use App\Repositories\Swoole\Traits\ClonesCollections;
use Illuminate\Support\Collection;

/**
 * @method Enemy             create(array $attributes)
 * @method null              find(int $id, array|string $columns = ['*'])
 * @method Enemy             findOrFail(int $id, array|string $columns = ['*'])
 * @method Enemy             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool              save(Enemy $model)
 * @method bool              update(Enemy $model, array $attributes = [], array $options = [])
 * @method bool              delete(Enemy $model)
 * @method Collection<Enemy> all()
 */
class EnemyRepositorySwoole extends EnemyRepository implements EnemyRepositorySwooleInterface
{
    use ClonesCollections;

    /** @var Collection<Collection<Enemy>> */
    private Collection $availableEnemiesByMappingVersion;

    public function __construct()
    {
        parent::__construct();

        $this->availableEnemiesByMappingVersion = collect();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
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
