<?php

namespace App\Service\MDT\Export;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

class PullExporter
{
    public function __construct(
        private readonly CacheServiceInterface       $cacheService,
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * Builds MDT's pulls array from this route's kill zones.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function export(DungeonRoute $dungeonRoute, MappingVersion $mappingVersion, Collection $warnings): array
    {
        $result = [];

        // Get a list of MDT enemies as Keystone.guru enemies - we need this to know how to convert
        /** @var Collection<int, Enemy> $mdtEnemies */
        $mdtEnemies = new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeonRoute->dungeon)
            ->getClonesAsEnemies($mappingVersion, $dungeonRoute->dungeon->floors);

        // Lua is 1 based, not 0 based
        $pullIndex = 1;
        /** @var Collection<int, KillZone> $killZones */
        $killZones = $dungeonRoute->loadMissing(['killZones.enemies.floor'])->killZones;
        foreach ($killZones as $killZone) {
            $pull = [];

            // Lua is 1 based, not 0 based
            $enemyIndex      = 1;
            $enemiesAdded    = 0;
            $killZoneEnemies = $killZone->getEnemies();
            foreach ($killZoneEnemies as $enemy) {
                // MDT does not handle prideful NPCs
                if ($enemy->npc->isPrideful()) {
                    continue;
                }

                // Find the MDT enemy - we need to know the mdt_npc_index
                $mdtNpcIndex = -1;
                foreach ($mdtEnemies as $mdtEnemyCandidate) {
                    if ($mdtEnemyCandidate->npc_id === $enemy->getMdtNpcId() && $mdtEnemyCandidate->mdt_id === $enemy->mdt_id) {
                        $mdtNpcIndex = $mdtEnemyCandidate->mdt_npc_index;
                        break;
                    }
                }

                // If we couldn't find the enemy in MDT..
                if ($mdtNpcIndex === -1) {
                    // Add a warning as long as it's not a boss - we don't particularly care since they have 0 count anyways
                    if (!$enemy->npc->isBoss()) {
                        $warnings->push(new ImportWarning(
                            sprintf(__('services.mdt.io.export_string.category.pull'), $pullIndex),
                            sprintf(__('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy'), __($enemy->npc->name), $enemy->id, $enemy->getMdtNpcId()),
                            ['details' => __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy_details')],
                        ));
                    }

                    continue;
                }

                // Create an array if it didn't exist yet
                if (!isset($pull[$mdtNpcIndex])) {
                    $pull[$mdtNpcIndex] = [];
                }

                // For this enemy, kill this clone
                $pull[$mdtNpcIndex][] = $enemy->mdt_id;
                $enemiesAdded++;
            }

            // A pull whose every enemy is unknown to MDT would export as an empty pull - warn instead
            if ($killZoneEnemies->count() !== 0 && $enemiesAdded === 0) {
                $warnings->push(new ImportWarning(
                    sprintf(__('services.mdt.io.export_string.category.pull'), $pullIndex),
                    __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_caused_empty_pull'),
                ));

                continue;
            }

            $pull['color'] = str_starts_with($killZone->color, '#') ? substr($killZone->color, 1) : $killZone->color;

            $result[$pullIndex++] = $pull;
        }

        return $result;
    }
}
