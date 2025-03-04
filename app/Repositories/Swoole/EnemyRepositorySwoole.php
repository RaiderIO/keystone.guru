<?php

namespace App\Repositories\Swoole;

use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Swoole\Interfaces\EnemyRepositorySwooleInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @method Enemy create(array $attributes)
 * @method null find(int $id, array|string $columns = ['*'])
 * @method Enemy findOrFail(int $id, array|string $columns = ['*'])
 * @method Enemy findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(Enemy $model)
 * @method bool update(Enemy $model, array $attributes = [], array $options = [])
 * @method bool delete(Enemy $model)
 * @method Collection<Enemy> all()
 */
class EnemyRepositorySwoole extends DatabaseRepository implements EnemyRepositorySwooleInterface
{
    private Collection $availableEnemiesByMappingVersion;

    public function __construct()
    {
        parent::__construct(Enemy::class);

        $this->availableEnemiesByMappingVersion = collect();
    }

    /**
     * @inheritDoc
     */
    public function getAvailableEnemiesForDungeonRouteBuilder(MappingVersion $mappingVersion): Collection
    {
        if ($this->availableEnemiesByMappingVersion->has($mappingVersion->id)) {
            return $this->availableEnemiesByMappingVersion->get($mappingVersion->id);
        }

        $availableEnemies = $mappingVersion->enemies()->with([
            'floor',
            'floor.dungeon',
            'enemyPack',
            'enemyPatrol',
        ])->where(function (Builder $builder) {
            $builder->whereNull('seasonal_type')
                ->orWhereNot('seasonal_type', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER);
        })->get()
            ->each(static function (Enemy $enemy) {
                // Ensure that the kill priority is 0 if it wasn't set
                $enemy->kill_priority ??= 0;
            })
            ->sort(static fn(Enemy $enemy) => $enemy->enemy_patrol_id ?? 0)
            ->keyBy('id');

        $this->availableEnemiesByMappingVersion->put($mappingVersion->id, $availableEnemies);
        return $availableEnemies;
    }
}
