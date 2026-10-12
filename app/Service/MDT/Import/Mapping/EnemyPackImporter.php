<?php

namespace App\Service\MDT\Import\Mapping;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use App\Service\MDT\Import\Traits\FindsSavedEnemyFromCloneEnemy;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use Exception;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

class EnemyPackImporter
{
    use FindsSavedEnemyFromCloneEnemy;

    public function __construct(
        private readonly MDTMappingImportServiceLoggingInterface $log,
    ) {
    }

    /**
     * @param  Collection<int, Enemy>   $savedEnemies
     * @throws InvalidArgumentException
     */
    public function importEnemyPacks(
        MappingVersion $newMappingVersion,
        MDTDungeon     $mdtDungeon,
        Dungeon        $dungeon,
        Collection     $savedEnemies,
    ): void {
        try {
            $this->log->importEnemyPacksStart();

            $savedEnemies = $savedEnemies->keyBy('id');

            // Get a list of enemies from the new mapping version - these contain the correct Lat/Lngs
            $newMappingVersionEnemies = $newMappingVersion
                ->enemies()
                ->whereIn('floor_id', $dungeon->floors()->active()->get()->pluck('id'))
                ->get();

            // Conserve the enemy_pack_id
            $mdtEnemiesWithGroups = $mdtDungeon->getClonesAsEnemies($newMappingVersion, $dungeon->floors()->active()->get());
            $mdtEnemyPacks        = $mdtEnemiesWithGroups->groupBy('enemy_pack_id');

            // Save enemy packs
            foreach ($mdtEnemyPacks as $groupIndex => $mdtEnemiesWithGroupsByEnemyPack) {
                /** @var Collection<int, Enemy> $mdtEnemiesWithGroupsByEnemyPack */
                $mdtEnemiesWithGroupsByEnemyPack = $mdtEnemiesWithGroupsByEnemyPack
                    ->filter(static fn(
                        Enemy $enemy,
                    ) => $enemy->teeming === null && !in_array($enemy->npc_id, EnemyImporter::IGNORE_ENEMY_NPC_IDS))
                    ->keyBy('id');

                // Enemies without a group - don't import that group, or no enemies assigned to the group
                if ($groupIndex === -1 || $mdtEnemiesWithGroupsByEnemyPack->isEmpty()) {
                    continue;
                }

                // We cannot use the enemies from MDT directly since they may contain an incorrect lat/lng
                // We do not re-import the lat/lng from MDT - we allow ourselves to adjust the lat/lng, so we must
                // fetch the adjusted lat/lng by matching enemies with what we actually saved
                // 1. Get a list of unique keys which we must look for in the real enemy list
                $enemiesWithGroupsByEnemyPackUniqueIds = $mdtEnemiesWithGroupsByEnemyPack->map(static fn(
                    Enemy $enemy,
                ) => $enemy->getUniqueKey());
                // 2. Find the enemies that were saved in the database by key
                $boundingBoxEnemies = $newMappingVersionEnemies->filter(static fn(
                    Enemy $enemy,
                ) => $enemiesWithGroupsByEnemyPackUniqueIds->search($enemy->getUniqueKey()) !== false);

                /** @var Enemy $firstEnemy */
                $firstEnemy = ($boundingBoxEnemies->first() ?? $mdtEnemiesWithGroupsByEnemyPack->first());

                $enemyPack = EnemyPack::create([
                    'mapping_version_id' => $newMappingVersion->id,
                    'floor_id'           => $firstEnemy->floor_id,
                    'group'              => $groupIndex,
                    'teeming'            => null,
                    'faction'            => Faction::FACTION_ANY,
                    'label'              => sprintf('Imported from MDT - group %d', $groupIndex),
                ]);
                $enemyPackPolyline = Polyline::create([
                    'model_id'    => $enemyPack->id,
                    'model_class' => EnemyPack::class,
                    'color'       => EnemyPack::DEFAULT_COLOR,
                    'weight'      => EnemyPack::DEFAULT_WEIGHT,
                    // 3. Create a new bounding box according to the new enemies lat/lngs
                    'vertices_json' => json_encode($this->getVerticesBoundingBoxFromEnemies($boundingBoxEnemies)),
                ]);
                $enemyPack->update(['polyline_id' => $enemyPackPolyline->id]);
                $this->log->importEnemyPacksSaveNewEnemyPackOK($enemyPack->id, $mdtEnemiesWithGroupsByEnemyPack->count());

                try {
                    $this->log->importEnemyPacksCoupleEnemyToPackStart($enemyPack->id);

                    foreach ($mdtEnemiesWithGroupsByEnemyPack as $enemyWithGroup) {
                        // In the list of enemies that we saved to the database, find the enemy that still had the group intact.
                        // Write the saved enemy's enemy pack back to the database
                        $savedEnemy = $this->findSavedEnemyFromCloneEnemy($savedEnemies, $enemyWithGroup->npc_id, $enemyWithGroup->mdt_id);

                        if ($savedEnemy->update(['enemy_pack_id' => $enemyPack->id])) {
                            $this->log->importEnemyPacksCoupleEnemyToEnemyPack($savedEnemy->id);
                        } else {
                            throw new Exception('Unable to update enemy with enemy pack!');
                        }
                    }
                } finally {
                    $this->log->importEnemyPacksCoupleEnemyToPackEnd();
                }
            }
        } finally {
            $this->log->importEnemyPacksEnd();
        }
    }

    /**
     * Get a bounding box which encompasses all passed enemies
     *
     * @param  Collection<int, Enemy>               $enemies
     * @return array<int, array<string, int|float>>
     */
    private function getVerticesBoundingBoxFromEnemies(Collection $enemies): array
    {
        $minLat = 1000;
        $minLng = 1000;
        $maxLat = -1000;
        $maxLng = -1000;
        foreach ($enemies as $enemy) {
            // Find the min and max of lat and lng so we have a nice square
            if ($minLat > $enemy->lat) {
                $minLat = $enemy->lat;
            }

            if ($maxLat < $enemy->lat) {
                $maxLat = $enemy->lat;
            }

            if ($minLng > $enemy->lng) {
                $minLng = $enemy->lng;
            }

            if ($maxLng < $enemy->lng) {
                $maxLng = $enemy->lng;
            }
        }

        // Expand the box a bit
        $padding = 1;
        $minLat -= $padding;
        $minLng -= $padding;
        $maxLat += $padding;
        $maxLng += $padding;

        // Create a box
        return [
            [
                'lat' => $minLat,
                'lng' => $minLng,
            ],
            [
                'lat' => $maxLat,
                'lng' => $minLng,
            ],
            [
                'lat' => $maxLat,
                'lng' => $maxLng,
            ],
            [
                'lat' => $minLat,
                'lng' => $maxLng,
            ],
        ];
    }
}
