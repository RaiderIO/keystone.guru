<?php

namespace App\Service\MDT\Import\Mapping;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTPatrol;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Import\Traits\FindsSavedEnemyFromCloneEnemy;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use Exception;
use Illuminate\Support\Collection;

class EnemyPatrolImporter
{
    use FindsSavedEnemyFromCloneEnemy;

    public function __construct(
        private readonly CoordinatesServiceInterface             $coordinatesService,
        private readonly MDTMappingImportServiceLoggingInterface $log,
    ) {
    }

    /**
     * @param  Collection<int, Enemy> $savedEnemies
     * @throws Exception
     */
    public function importEnemyPatrols(
        ?MappingVersion $currentMappingVersion,
        MappingVersion  $newMappingVersion,
        MDTDungeon      $mdtDungeon,
        Dungeon         $dungeon,
        Collection      $savedEnemies,
    ): void {
        try {
            $this->log->importEnemyPatrolsStart();

            // Get a list of new enemies and save them
            $mdtNPCs = $mdtDungeon->getMDTNPCs();

            // Pretend the patrol is on the facade floor for correct translations, IF the facade floor is available
            $facadeFloor = $dungeon->getFacadeFloor();

            // No predecessor - nothing to match against or re-clone below.
            /** @var Collection<int, EnemyPatrol> $existingEnemyPatrols */
            $existingEnemyPatrols = $currentMappingVersion
                ?->enemyPatrols()
                ->with([
                    'polyline',
                    'mdtPolyline',
                ])
                ->get() ?? collect();

            foreach ($mdtNPCs as $mdtNPC) {
                foreach ($mdtNPC->getRawMdtNpc()['clones'] as $mdtCloneIndex => $mdtNpcClone) {
                    if (!isset($mdtNpcClone['patrol'])) {
                        continue;
                    }

                    if (isset($mdtNpcClone['teeming'])) {
                        continue;
                    }

                    try {
                        $savedEnemy = $this->findSavedEnemyFromCloneEnemy($savedEnemies, $mdtNPC->getId(), $mdtCloneIndex);
                    } catch (Exception $exception) {
                        $this->log->importEnemyPatrolsUnableToFindAttachedEnemy($mdtCloneIndex, $mdtNpcClone, $mdtNPC->getId(), $mdtCloneIndex);

                        throw $exception;
                    }

                    $this->log->importEnemyPatrolsEnemyHasPatrol($savedEnemy->getUniqueKey());

                    if (empty($mdtNpcClone['patrol'])) {
                        $this->log->importEnemyPatrolsFoundPatrolIsEmpty($savedEnemy->getUniqueKey());

                        continue;
                    }

                    // We check the MDTPolyline to see if it equals the one we are about to save
                    // If it does, we re-import the existing enemy patrol instead (since it may have been altered manually)
                    // Not doing this would overwrite any manual changes made to the enemy patrols
                    $mdtPolylineVerticesJson = json_encode(array_values(array_map(fn(array $v) => [
                        'lat' => $v['y'],
                        'lng' => $v['x'],
                    ], $mdtNpcClone['patrol'])));

                    $existingEnemyPatrol = null;
                    foreach ($existingEnemyPatrols as $existingEnemyPatrolCandidate) {
                        // See if we imported this patrol already
                        // It could be that MDT has changed the patrol slightly, we'll then have to re-do our changes
                        // Patrols are otherwise hard or impossible to match (in theory could be done with the enemies
                        // attached to the patrol, but that would be a lot of work)
                        if ($existingEnemyPatrolCandidate->mdtPolyline?->vertices_json === $mdtPolylineVerticesJson) {
                            $existingEnemyPatrol = $existingEnemyPatrolCandidate;
                            $this->log->importEnemyPatrolsFoundExistingEnemyPatrol($existingEnemyPatrol->id);
                            break;
                        }
                    }

                    $verticesAttributes = $existingEnemyPatrol?->polyline->getAttributes() ?? null;
                    if (empty($verticesAttributes)) {
                        $vertices = [];
                        foreach ($mdtNpcClone['patrol'] as $xy) {
                            $latLng     = Conversion::convertMDTCoordinateToLatLng($xy, $facadeFloor ?? $savedEnemy->floor);
                            $latLng     = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);
                            $vertices[] = $latLng->toArray();
                        }

                        // MDT automatically closes up the patrol which I don't, so correct for this (confirmed by Nnoggie)
                        $vertices[]         = $vertices[0];
                        $verticesAttributes = [
                            'model_class'    => EnemyPatrol::class,
                            'color'          => '#003280',
                            'color_animated' => null,
                            'weight'         => 2,
                            'vertices_json'  => json_encode($vertices),
                        ];
                    }

                    // Save polyline
                    $polyLine = Polyline::create(array_merge($verticesAttributes, [
                        // Reset the ID (in case it was imported from an existing enemy patrol)
                        'id' => null,
                        // But make sure that the polyline is not attached to any model yet
                        'model_id' => -1,
                    ]));
                    $this->log->importEnemyPatrolsSaveNewPolyline($polyLine->id);

                    // MDT Polyline
                    $mdtPolyLine = Polyline::create([
                        'model_id'       => -1,
                        'model_class'    => MDTPatrol::class,
                        'color'          => '#003280',
                        'color_animated' => null,
                        'weight'         => 2,
                        // Save the direct X and Y coordinates as lat/lng so we can echo it back later exactly
                        // This polyline is not meant to be used for display, but rather to be used for MDT
                        'vertices_json' => $mdtPolylineVerticesJson,
                    ]);
                    $this->log->importEnemyPatrolsSaveNewMdtPolyline($mdtPolyLine->id);

                    // Enemy patrols
                    $enemyPatrol = EnemyPatrol::create([
                        'mapping_version_id' => $newMappingVersion->id,
                        'floor_id'           => $savedEnemy->floor_id,
                        'polyline_id'        => $polyLine->id,
                        'mdt_polyline_id'    => $mdtPolyLine->id,
                        'mdt_npc_id'         => $mdtNPC->getId(),
                        'mdt_id'             => $mdtCloneIndex,
                        'teeming'            => null,
                        'faction'            => Faction::FACTION_ANY,
                    ]);
                    $this->log->importEnemyPatrolsSaveNewEnemyPatrol($enemyPatrol->id);

                    // Couple polyline to enemy patrol
                    $polyLineSaveResult = $polyLine->update(['model_id' => $enemyPatrol->id]);
                    if ($polyLineSaveResult) {
                        $this->log->importEnemyPatrolsCoupleEnemyPatrolToPolyline($enemyPatrol->id, $polyLine->id);
                    } else {
                        throw new Exception('Unable to save polyline!');
                    }
                    // Couple mdt polyline to enemy patrol
                    $mdtPolyLineSaveResult = $mdtPolyLine->update(['model_id' => $enemyPatrol->id]);
                    if ($mdtPolyLineSaveResult) {
                        $this->log->importEnemyPatrolsCoupleEnemyPatrolToPolyline($enemyPatrol->id, $polyLine->id);
                    } else {
                        throw new Exception('Unable to save polyline!');
                    }

                    // Couple enemy/enemies to enemy patrol
                    if ($savedEnemy->enemy_pack_id !== null) {
                        $enemyUpdateResult = Enemy::where('enemy_pack_id', $savedEnemy->enemy_pack_id)
                            ->update(['enemy_patrol_id' => $enemyPatrol->id]);
                    } else {
                        $enemyUpdateResult = $savedEnemy->update(['enemy_patrol_id' => $enemyPatrol->id]);
                    }

                    if ($enemyUpdateResult) {
                        $this->log->importEnemyPatrolsCoupleEnemiesToEnemyPatrol($enemyPatrol->id);
                    } else {
                        throw new Exception('Unable to update enemy to have attached patrol!');
                    }
                }
            }

            // Import any enemy patrols created that do not have a MDT polyline
            // These patrols may be linked to connecting portals or skip icons together.
            // They just use a patrol to make it look nice.
            if ($existingEnemyPatrols->filter(
                // Only import enemy patrols like this if there was ever an mdt polyline created for them
                // On older mapping versions, enemy patrols were created without MDT polyline
                static fn(EnemyPatrol $enemyPatrol) => $enemyPatrol->mdtPolyline !== null,
            )->isNotEmpty()) {
                foreach ($existingEnemyPatrols as $existingEnemyPatrolCandidate) {
                    if ($existingEnemyPatrolCandidate->mdtPolyline === null) {
                        // Clone the existing enemy patrol to the new mapping version
                        $existingEnemyPatrolCandidate->cloneForNewMappingVersion(
                            $newMappingVersion,
                        );

                        $this->log->importEnemyPatrolsClonedPatrolWithoutMdtPolyline(isset($enemyPatrol) ? $enemyPatrol->id : null);
                    }
                }
            }
        } finally {
            $this->log->importEnemyPatrolsEnd();
        }
    }
}
