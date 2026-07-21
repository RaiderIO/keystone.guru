<?php

namespace App\Service\MDT\Import;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\RaidMarker;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Models\ImportStringRaidMarkers;
use Illuminate\Support\Collection;

class RaidMarkerImporter
{
    public function __construct(
        private readonly CacheServiceInterface       $cacheService,
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * Parse $decoded['value']['enemyAssignments'] - MDT's raid target icon assignments - into
     * resolved DungeonRouteEnemyRaidMarker attributes on the ImportStringRaidMarkers object.
     *
     * enemyAssignments is shaped {mdtNpcIndex: {mdtCloneIndex: raidTargetIndex}}, addressing the
     * exact same MDT npc index / clone index space that pulls use (see PullImporter), with
     * raidTargetIndex being WoW's standard 1-8 raid target icon numbering, matching RaidMarker::ALL.
     */
    public function parseEnemyAssignments(ImportStringRaidMarkers $importStringRaidMarkers): ImportStringRaidMarkers
    {
        $dungeon = $importStringRaidMarkers->getDungeon();
        $floors  = $dungeon->floors;

        /** @var Collection<int, Enemy> $enemies */
        $enemies = $importStringRaidMarkers->getMappingVersion()->enemies->each(static function (Enemy $enemy): void {
            $enemy->npc_id = $enemy->mdt_npc_id ?? $enemy->npc_id;
        });
        $enemiesByNpcId = $enemies->groupBy('npc_id');

        $mdtEnemies = new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeon)
            ->getClonesAsEnemies($importStringRaidMarkers->getMappingVersion(), $floors);
        $mdtEnemiesByMdtNpcIndex = $mdtEnemies->groupBy('mdt_npc_index');

        $validRaidMarkerIds = array_flip(RaidMarker::ALL);

        $mdtEnemyAssignments = $importStringRaidMarkers->getMdtEnemyAssignments();

        // MDT npc indices, like clone indices below, are always >= 1. The same array-vs-map
        // ambiguity applies here: if enemyAssignments as a whole happens to have npc indices
        // starting at 1 with no gaps, it round-trips back as a 0-indexed JSON array instead of
        // {npcIndex: {...}}. Detect and shift the same way.
        $npcIndicesWereCoercedToList = array_is_list($mdtEnemyAssignments);

        foreach ($mdtEnemyAssignments as $mdtNpcIndexKey => $cloneAssignments) {
            // Numeric means it's an index of the dungeon's NPCs; skip anything else
            if (!is_numeric($mdtNpcIndexKey) || !is_array($cloneAssignments)) {
                continue;
            }

            $npcIndex = $npcIndicesWereCoercedToList ? ((int)$mdtNpcIndexKey + 1) : (int)$mdtNpcIndexKey;

            // MDT clone indices are always >= 1 ("All MDT_IDs are 1-indexed, because LUA" - see
            // MDTDungeon::getClonesAsEnemies). The JSON bridge (mdt:encode/mdt:decode) can't tell a
            // Lua table used as a map from one used as a sequential array, so a set of clone indices
            // that happens to start at 1 with no gaps round-trips back as a 0-indexed JSON array
            // instead of {cloneIndex: raidTargetIndex}. Detect that and shift the (0-based) array
            // position back to a (1-based) clone index.
            $cloneIndicesWereCoercedToList = array_is_list($cloneAssignments);

            foreach ($cloneAssignments as $mdtCloneIndexKey => $rawRaidMarkerId) {
                if (!is_numeric($mdtCloneIndexKey)) {
                    continue;
                }

                $raidMarkerId = (int)$rawRaidMarkerId;
                if (!isset($validRaidMarkerIds[$raidMarkerId])) {
                    continue;
                }

                $mdtCloneIndex = $cloneIndicesWereCoercedToList ? ((int)$mdtCloneIndexKey + 1) : (int)$mdtCloneIndexKey;
                $cloneIndex    = $this->applyDungeonCloneIndexHack($dungeon, $npcIndex, $mdtCloneIndex);

                $mdtEnemy = null;
                if ($mdtEnemiesByMdtNpcIndex->has($npcIndex)) {
                    foreach ($mdtEnemiesByMdtNpcIndex->get($npcIndex) as $mdtEnemyCandidate) {
                        /** @var Enemy $mdtEnemyCandidate */
                        if ($mdtEnemyCandidate->mdt_id === $cloneIndex) {
                            $mdtEnemy = $mdtEnemyCandidate;

                            break;
                        }
                    }
                }

                if ($mdtEnemy === null) {
                    $importStringRaidMarkers->getWarnings()->push(new ImportWarning(
                        __('services.mdt.io.import_string.category.raid_markers'),
                        sprintf(__('services.mdt.io.import_string.unable_to_find_mdt_enemy_for_clone_index'), $cloneIndex, $npcIndex),
                        ['details' => __('services.mdt.io.import_string.unable_to_find_mdt_enemy_for_clone_index_details')],
                    ));

                    continue;
                }

                $enemy = null;
                if ($enemiesByNpcId->has($mdtEnemy->npc_id)) {
                    foreach ($enemiesByNpcId->get($mdtEnemy->npc_id) as $enemyCandidate) {
                        /** @var Enemy $enemyCandidate */
                        if ($enemyCandidate->mdt_id === $mdtEnemy->mdt_id) {
                            $enemy = $enemyCandidate;

                            break;
                        }
                    }
                }

                if ($enemy === null) {
                    $importStringRaidMarkers->getWarnings()->push(new ImportWarning(
                        __('services.mdt.io.import_string.category.raid_markers'),
                        sprintf(
                            __('services.mdt.io.import_string.unable_to_find_kg_equivalent_for_mdt_enemy'),
                            $mdtEnemy->mdt_id,
                            __($mdtEnemy->npc->name),
                            $mdtEnemy->npc_id,
                        ),
                        ['details' => __('services.mdt.io.import_string.unable_to_find_kg_equivalent_for_mdt_enemy_details')],
                    ));

                    continue;
                }

                $importStringRaidMarkers->addRaidMarkerAttributes([
                    'npc_id'         => $enemy->npc_id,
                    'mdt_id'         => $enemy->mdt_id,
                    'enemy_id'       => $enemy->id,
                    'raid_marker_id' => $raidMarkerId,
                ]);
            }
        }

        return $importStringRaidMarkers;
    }

    public function applyRaidMarkersToDungeonRoute(ImportStringRaidMarkers $importStringRaidMarkers, DungeonRoute $dungeonRoute): void
    {
        $raidMarkerAttributes = $importStringRaidMarkers->getRaidMarkerAttributes();

        if ($raidMarkerAttributes->isEmpty()) {
            return;
        }

        DungeonRouteEnemyRaidMarker::insert($raidMarkerAttributes->map(
            static fn(array $attributes) => $attributes + ['dungeon_route_id' => $dungeonRoute->id],
        )->all());
    }

    /**
     * Mirrors PullImporter::parseMdtNpcClonesInPull's dungeon-specific clone index hack: MDT lists
     * these NPCs twice under different mdt npc indices, whose clone index ranges collide unless
     * offset. Keep both in sync.
     */
    private function applyDungeonCloneIndexHack(Dungeon $dungeon, int $npcIndex, int $cloneIndex): int
    {
        if ($dungeon->key === Dungeon::DUNGEON_SIEGE_OF_BORALUS && $npcIndex === 35) {
            return $cloneIndex + 15;
        }

        if ($dungeon->key === Dungeon::DUNGEON_TOL_DAGOR && $npcIndex === 11) {
            return $cloneIndex + 2;
        }

        if ($dungeon->key === Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE && $npcIndex === 23) {
            return $cloneIndex + 5;
        }

        return $cloneIndex;
    }
}
