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
    /**
     * @return Collection<int, Enemy>
     */
    public function getAvailableEnemiesForDungeonRouteBuilder(MappingVersion $mappingVersion): Collection
    {
        return $mappingVersion->enemies()->with([
            'floor',
            'floor.dungeon',
            'enemyPack',
            'enemyPatrol',
            'npc',
        ])->where(function (Builder $builder) {
            $builder->whereNull('seasonal_type')
                ->orWhereNot('seasonal_type', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER);
        })->get()
            ->each(static function (Enemy $enemy) {
                // Ensure that the kill priority is 0 if it wasn't set
                $enemy->kill_priority ??= 0;
            })
            ->sortBy(static fn(Enemy $enemy) => $enemy->enemy_patrol_id ?? 0)
            ->keyBy('id');
    }

    public function updateNpcId(int $oldNpcId, int $newNpcId): int
    {
        return Enemy::where('npc_id', $oldNpcId)->update(['npc_id' => $newNpcId]);
    }

    public function hasDungeonDifficultyEnemies(MappingVersion $mappingVersion): bool
    {
        return $mappingVersion->enemies()->whereNotNull('dungeon_difficulty')->exists();
    }
}
