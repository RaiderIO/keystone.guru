<?php

namespace App\Service\MDT\Export;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class EnemyAssignmentExporter
{
    public function __construct(
        private readonly CacheServiceInterface       $cacheService,
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * Builds MDT's raid target icon assignments ({mdtNpcIndex: {mdtCloneIndex: raidTargetIndex}}) -
     * the counterpart consumed by RaidMarkerImporter on import - from this route's raid markers.
     * npc_id/mdt_id on DungeonRouteEnemyRaidMarker are already the durable, mapping-version-current
     * identity, so they are read directly rather than through the enemy_id.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    public function export(DungeonRoute $dungeonRoute, MappingVersion $mappingVersion, Collection $warnings): array
    {
        $result = [];

        $enemyRaidMarkers = $dungeonRoute->enemyRaidMarkers;
        if ($enemyRaidMarkers->isEmpty()) {
            return $result;
        }

        /** @var Collection<int, Enemy> $mdtEnemies */
        $mdtEnemies = new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeonRoute->dungeon)
            ->getClonesAsEnemies($mappingVersion, $dungeonRoute->dungeon->floors);

        foreach ($enemyRaidMarkers as $enemyRaidMarker) {
            $mdtNpcIndex = -1;
            foreach ($mdtEnemies as $mdtEnemyCandidate) {
                if ($mdtEnemyCandidate->npc_id === $enemyRaidMarker->npc_id && $mdtEnemyCandidate->mdt_id === $enemyRaidMarker->mdt_id) {
                    $mdtNpcIndex = $mdtEnemyCandidate->mdt_npc_index;
                    break;
                }
            }

            if ($mdtNpcIndex === -1) {
                $warnings->push(new ImportWarning(
                    __('services.mdt.io.export_string.category.raid_markers'),
                    sprintf(
                        __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_raid_marker'),
                        $enemyRaidMarker->raidMarker->name,
                        $enemyRaidMarker->npc_id,
                    ),
                    ['details' => __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy_details')],
                ));

                continue;
            }

            $result[$mdtNpcIndex] ??= [];
            $result[$mdtNpcIndex][$enemyRaidMarker->mdt_id] = $enemyRaidMarker->raid_marker_id;
        }

        return $result;
    }
}
