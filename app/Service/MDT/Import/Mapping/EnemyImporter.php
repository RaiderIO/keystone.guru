<?php

namespace App\Service\MDT\Import\Mapping;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyForcesCheckpoint;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use Exception;
use Illuminate\Support\Collection;

class EnemyImporter
{
    /** @var array<int, int> Ignore these enemies when their NPC ID is in this list */
    public const array IGNORE_ENEMY_NPC_IDS = [
        // Black Rook Hold, Troubled Soul
        98362,
    ];

    private const array IGNORE_ENEMY_DISTANCE_CHECK_NPC_IDS = [
        // Darkflame Cleft, The Darkness - we move it to an entirely different area so please ignore this check
        208747,
    ];

    public function __construct(
        private readonly CoordinatesServiceInterface             $coordinatesService,
        private readonly MDTMappingImportServiceLoggingInterface $log,
    ) {
    }

    /**
     * @param  array<int, int>        $enemyForcesCheckpointIdMapping Source checkpoint id => cloned checkpoint id, as
     *                                                                built by MappingService when it cloned the
     *                                                                previous mapping version's checkpoints.
     * @param  array<int, Exception>  $failures                       Appended with enemy saves that failed - see
     *                                                                importNpcsDataFromMDT()'s $failures parameter.
     * @return Collection<int, Enemy>
     */
    public function importEnemies(
        ?MappingVersion $currentMappingVersion,
        MappingVersion  $newMappingVersion,
        MDTDungeon      $mdtDungeon,
        Dungeon         $dungeon,
        array           $enemyForcesCheckpointIdMapping,
        bool            $forceImport,
        array           &           $failures,
    ): Collection {
        // Get a list of new enemies and save them
        try {
            $this->log->importEnemiesStart();

            // No predecessor (e.g. this dungeon's first-ever import for this game version) - nothing to
            // recover properties from, every MDT enemy below is treated as brand new (#3757).
            $currentEnemies = $currentMappingVersion
                ?->enemies()
                ->with('floor')
                ->get()
                ->keyBy(static fn(Enemy $enemy) => $enemy->getUniqueKey()) ?? collect();

            $enemies = $mdtDungeon->getClonesAsEnemies($newMappingVersion, $dungeon->floors()->active()->get());

            foreach ($enemies as $mdtEnemy) {
                if (in_array($mdtEnemy->npc_id, self::IGNORE_ENEMY_NPC_IDS)) {
                    $this->log->importEnemiesSkipIgnoredByNpcEnemy($mdtEnemy->getUniqueKey());

                    continue;
                }

                // Skip teeming enemies for now - this affix was removed ages ago
                if ($mdtEnemy->teeming !== null) {
                    $this->log->importEnemiesSkipTeemingEnemy($mdtEnemy->getUniqueKey());

                    continue;
                }

                $mdtEnemy->exists = false;
                $mdtEnemy->unsetRelations();

                // Not saved in the database
                unset($mdtEnemy->npc);
                unset($mdtEnemy->id);
                unset($mdtEnemy->mdt_npc_index);
                unset($mdtEnemy->is_mdt);
                unset($mdtEnemy->enemy_id);

                // Is group ID - we handle this later on
                $mdtEnemy->enemy_pack_id = null;
                // Membership is restored below, but only for enemies we can match to their predecessor
                $mdtEnemy->enemy_forces_checkpoint_id = null;
                $mdtEnemy->mapping_version_id         = $newMappingVersion->id;

                $existingEnemy = $currentEnemies->get($mdtEnemy->getUniqueKey());
                if ($existingEnemy instanceof Enemy) {
                    $fields = [
                        'teeming',
                        'faction',
                        'required',
                        'skippable',
                        'kill_priority',
                    ];
                    // We ignore MDT's position - we want to keep agency in the location we place enemies still
                    // since we value VERY MUCH the enemy location being accurate to where they are in-game
                    if (!$forceImport) {
                        $fields[] = 'floor_id';

                        if (!in_array($mdtEnemy->npc_id, self::IGNORE_ENEMY_DISTANCE_CHECK_NPC_IDS) &&
                            ($distance = $this->coordinatesService->distanceIngameXY(
                                $this->coordinatesService->calculateIngameLocationForMapLocation($existingEnemy->getLatLng()),
                                $this->coordinatesService->calculateIngameLocationForMapLocation($mdtEnemy->getLatLng()),
                            )) > 150) {
                            $this->log->importEnemiesDistanceTooLargeNotTransferringExistingEnemyLatLng($mdtEnemy->getUniqueKey(), $distance);
                        } else {
                            // Ok, copy over lat/lng
                            $fields = array_merge($fields, [
                                'lat',
                                'lng',
                            ]);
                        }
                    }

                    $updatedFields = [];
                    foreach ($fields as $field) {
                        $mdtEnemy->$field      = $existingEnemy->$field;
                        $updatedFields[$field] = $existingEnemy->$field;
                    }

                    // Special case - if we manually assigned the MDT placeholder, we would want to migrate that over as well.
                    // But all other seasonal types can be adjusted by MDT and we copy them back over.
                    if (in_array($existingEnemy->seasonal_type, [
                        Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER,
                        Enemy::SEASONAL_TYPE_REQUIRES_ACTIVATION,
                    ])) {
                        $mdtEnemy->seasonal_type        = $existingEnemy->seasonal_type;
                        $updatedFields['seasonal_type'] = $existingEnemy->seasonal_type;
                    }

                    // Enemy forces checkpoints were cloned into the new mapping version before we got here, so the
                    // predecessor's checkpoint id must be translated - copying it verbatim would attach this enemy to
                    // the previous mapping version's checkpoint (#3702).
                    if ($existingEnemy->enemy_forces_checkpoint_id !== null) {
                        $newEnemyForcesCheckpointId = $enemyForcesCheckpointIdMapping[$existingEnemy->enemy_forces_checkpoint_id] ?? null;

                        if ($newEnemyForcesCheckpointId === null) {
                            $this->log->importEnemiesCannotRecoverEnemyForcesCheckpoint(
                                $mdtEnemy->getUniqueKey(),
                                $existingEnemy->enemy_forces_checkpoint_id,
                            );
                        } else {
                            $mdtEnemy->enemy_forces_checkpoint_id        = $newEnemyForcesCheckpointId;
                            $updatedFields['enemy_forces_checkpoint_id'] = $newEnemyForcesCheckpointId;
                        }
                    }

                    // Enemy forces corrections are only ever entered by hand in the mapping editor, so without this
                    // a re-import silently resets them to null. MDT's own per-clone count wins where it has one - that
                    // is authoritative, and newer than a correction entered against an older MDT release.
                    foreach (['enemy_forces_override', 'enemy_forces_override_teeming'] as $enemyForcesField) {
                        if ($mdtEnemy->$enemyForcesField === null && $existingEnemy->$enemyForcesField !== null) {
                            $mdtEnemy->$enemyForcesField      = $existingEnemy->$enemyForcesField;
                            $updatedFields[$enemyForcesField] = $existingEnemy->$enemyForcesField;
                        }
                    }

                    $this->log->importEnemiesRecoverPropertiesFromExistingEnemy($mdtEnemy->getUniqueKey(), $updatedFields);
                } else {
                    $this->log->importEnemiesCannotRecoverPropertiesFromExistingEnemy($mdtEnemy->getUniqueKey());
                }

                try {
                    if ($mdtEnemy->save()) {
                        $this->log->importEnemiesSaveNewEnemy($mdtEnemy->id);
                    } else {
                        throw new Exception(sprintf('Unable to save enemy %d!', $mdtEnemy->id));
                    }
                } catch (Exception $exception) {
                    $this->log->importEnemiesSaveNewEnemyException($exception);
                    $failures[] = $exception;
                }
            }

            $this->deleteEmptyEnemyForcesCheckpoints($currentMappingVersion, $newMappingVersion, $enemyForcesCheckpointIdMapping);
        } finally {
            $this->log->importEnemiesEnd();
        }

        return $enemies;
    }

    /**
     * An MDT mapping bump is exactly when the enemy set changes. A checkpoint whose members were all removed
     * upstream - or which lost them because we could not match them to their predecessor - would otherwise
     * survive as an empty checkpoint reporting zero enemy forces, which is not what the mapper drew (#3702).
     *
     * A checkpoint that never had any members in the SOURCE mapping version did not lose anything by staying
     * empty here - most notably a "bare mapping version" checkpoint (MappingVersionController@saveNew's "Add
     * bare mapping version" action clones checkpoints without cloning any enemies), which is memberless by
     * construction until the mapper re-assigns enemies to it by hand. Only a clone whose source counterpart
     * genuinely had members - and lost them, or could not be matched to them - is pruned. Anything in the new
     * mapping version that is not in $enemyForcesCheckpointIdMapping's value set is therefore deliberately
     * left alone, even if it is currently empty.
     *
     * @param array<int, int> $enemyForcesCheckpointIdMapping Source checkpoint id => cloned checkpoint id, as
     *                                                        built by MappingService when it cloned the
     *                                                        previous mapping version's checkpoints.
     */
    private function deleteEmptyEnemyForcesCheckpoints(
        ?MappingVersion $currentMappingVersion,
        MappingVersion  $newMappingVersion,
        array           $enemyForcesCheckpointIdMapping,
    ): void {
        // No predecessor means $enemyForcesCheckpointIdMapping is already empty (nothing was cloned to prune
        // from) - nothing to do (#3757).
        if ($currentMappingVersion === null) {
            return;
        }

        /** @var Collection<int, EnemyForcesCheckpoint> $emptyEnemyForcesCheckpoints */
        $emptyEnemyForcesCheckpoints = $newMappingVersion->enemyForcesCheckpoints()
            ->whereDoesntHave('enemies')
            ->get();

        if ($emptyEnemyForcesCheckpoints->isEmpty()) {
            return;
        }

        /** @var array<int, int> $sourceIdsWithMembers Source checkpoint ids that had at least one member */
        $sourceIdsWithMembers = $currentMappingVersion->enemyForcesCheckpoints()
            ->has('enemies')
            ->pluck('id')
            ->all();
        // Clone ids whose SOURCE counterpart had members - only these may be pruned when still empty here.
        $prunableCloneIds = array_intersect_key($enemyForcesCheckpointIdMapping, array_flip($sourceIdsWithMembers));

        /** @var Collection<int, EnemyForcesCheckpoint> $enemyForcesCheckpointsToPrune */
        $enemyForcesCheckpointsToPrune = $emptyEnemyForcesCheckpoints->whereIn('id', array_values($prunableCloneIds));

        if ($enemyForcesCheckpointsToPrune->isEmpty()) {
            return;
        }

        foreach ($enemyForcesCheckpointsToPrune as $emptyEnemyForcesCheckpoint) {
            $this->log->importEnemiesDeleteEmptyEnemyForcesCheckpoint(
                $emptyEnemyForcesCheckpoint->id,
                $emptyEnemyForcesCheckpoint->name,
            );
        }
        // The per-checkpoint line above stays at warning, but the aggregate is worth paging on: after the fix
        // above, this only fires when a checkpoint that DID have members in the source could not be carried
        // over - which is exactly the silent whole-dungeon wipe this bug used to cause (#3702).
        $this->log->importEnemiesPrunedEmptyEnemyForcesCheckpoints(
            $newMappingVersion->dungeon_id,
            $enemyForcesCheckpointsToPrune->count(),
        );

        // A bulk query builder delete rather than looping model deletes: it skips per-model events, including
        // the EnemyForcesCheckpoint `deleted` hook - which only releases member enemies, and these checkpoints
        // have none by definition, so skipping it here is fine.
        EnemyForcesCheckpoint::query()
            ->whereIn('id', $enemyForcesCheckpointsToPrune->pluck('id'))
            ->delete();
    }
}
