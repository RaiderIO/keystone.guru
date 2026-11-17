<?php

namespace App\Repositories\Database;

use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcClassification;
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

    /**
     * @param  array<int, int>                  $mappingVersionIds
     * @return Collection<int, array<int, int>>
     */
    public function getBossNpcIdsPerMappingVersion(array $mappingVersionIds): Collection
    {
        if ($mappingVersionIds === []) {
            return collect();
        }

        /** @var array<int, array<int, int>> $bossNpcIdsPerMappingVersion */
        $bossNpcIdsPerMappingVersion = [];

        $enemies = Enemy::query()
            ->whereIn('mapping_version_id', $mappingVersionIds)
            ->where(static function (Builder $builder) {
                $builder->whereNull('seasonal_type')
                    ->orWhereNot('seasonal_type', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER);
            })
            ->whereHas('npc', static function (Builder $builder) {
                $builder->whereIn('classification_id', [
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
                ]);
            })
            ->distinct()
            ->get(['mapping_version_id', 'npc_id']);

        foreach ($enemies as $enemy) {
            /** @var Enemy $enemy */
            if ($enemy->npc_id === null) {
                continue;
            }

            $bossNpcIdsPerMappingVersion[$enemy->mapping_version_id][$enemy->npc_id] = $enemy->npc_id;
        }

        /** @var Collection<int, array<int, int>> $result */
        $result = collect($bossNpcIdsPerMappingVersion)->map(static fn(array $npcIds): array => array_values($npcIds));

        return $result;
    }
}
