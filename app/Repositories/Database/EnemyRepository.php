<?php

namespace App\Repositories\Database;

use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EnemyRepository extends DatabaseRepository implements EnemyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Enemy::class);
    }

    public function getAvailableEnemiesForDungeonRouteBuilder(MappingVersion $mappingVersion): Collection
    {
        return $mappingVersion->enemies()->with([
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
    }


}
