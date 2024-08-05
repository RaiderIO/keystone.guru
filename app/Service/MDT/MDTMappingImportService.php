<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcSpell;
use App\Models\Npc\NpcType;
use App\Models\Polyline;
use App\Models\Spell\Spell;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

class MDTMappingImportService implements MDTMappingImportServiceInterface
{
    /** @var array Ignore these enemies when their NPC ID is in this list */
    private const IGNORE_ENEMY_NPC_IDS = [
        // Black Rook Hold, Troubled Soul
        98362,
    ];

    public function __construct(private readonly CacheServiceInterface $cacheService, private readonly CoordinatesServiceInterface $coordinatesService, private readonly MDTMappingImportServiceLoggingInterface $log)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function importMappingVersionFromMDT(MappingServiceInterface $mappingService, Dungeon $dungeon, bool $forceImport = false): MappingVersion
    {
        $latestMdtMappingHash = $this->getMDTMappingHash($dungeon);

        $currentMappingVersion = $dungeon->currentMappingVersion;
        if ($forceImport || $currentMappingVersion->mdt_mapping_hash !== $latestMdtMappingHash) {
            $this->log->importMappingVersionFromMDTMappingChanged($currentMappingVersion->mdt_mapping_hash, $latestMdtMappingHash);

            $newMappingVersion = $mappingService->createNewMappingVersionFromMDTMapping($dungeon, $this->getMDTMappingHash($dungeon));
            $this->log->importMappingVersionFromMDTCreateMappingVersion($newMappingVersion->version, $newMappingVersion->id);

            $mdtDungeon = new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeon);

            try {
                $this->log->importMappingVersionFromMDTStart($dungeon->id);

                $this->importDungeon($mdtDungeon, $dungeon, $newMappingVersion);
                $this->importNpcs($newMappingVersion, $mdtDungeon, $dungeon);
                $enemies = $this->importEnemies($currentMappingVersion, $newMappingVersion, $mdtDungeon, $dungeon, $forceImport);
                $this->importEnemyPacks($newMappingVersion, $mdtDungeon, $dungeon, $enemies);
                $this->importEnemyPatrols($newMappingVersion, $mdtDungeon, $dungeon, $enemies);
                $this->importDungeonFloorSwitchMarkers($currentMappingVersion, $newMappingVersion, $mdtDungeon, $dungeon);
            } finally {
                $this->log->importMappingVersionFromMDTEnd();
            }

            return $newMappingVersion;
        } else {
            throw new Exception(
                sprintf('Most recent mapping version is already imported from this MDT version! (%s - %s)', $dungeon->key, $latestMdtMappingHash)
            );
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getMDTMappingHash(Dungeon $dungeon): string
    {
        $mdtDungeon = new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeon);

        return md5(
            json_encode([
                'counts'             => $mdtDungeon->getDungeonTotalCount(),
                'npcs'               => $mdtDungeon->getMDTNPCs()->toArray(),
                'floorSwitchMarkers' => $mdtDungeon->getMDTMapPOIs()->toArray(),
            ])
        );
    }

    public function importNpcsDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        try {
            $this->log->importNpcsDataFromMDTStart($dungeon->key);

            // Get a list of NPCs and update/save them
            $existingNpcs = $dungeon->npcs->keyBy('id');

            $characteristicsByName = Characteristic::all()->mapWithKeys(function (Characteristic $characteristic) {
                return [__($characteristic->name, [], 'en_US') => $characteristic];
            });

            $npcCharacteristicsAttributes = [];
            $npcSpellsAttributes          = [];
            $affectedNpcIds               = [];

            /** @var Npc|null $npc */
            foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
                $affectedNpcIds[] = $mdtNpc->getId();

                $npc = $existingNpcs->get($mdtNpc->getId());

                if ($newlyCreated = ($npc === null)) {
                    $npc = new Npc();
                }

                $npc->id = $mdtNpc->getId();
                // Allow manual override to -1
                $npc->dungeon_id        = $npc->dungeon_id === -1 ? -1 : $dungeon->id;
                $npc->display_id        = $mdtNpc->getDisplayId();
                $npc->encounter_id      = $mdtNpc->getEncounterId();
                $npc->classification_id ??= NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE];
                $npc->name              = $mdtNpc->getName();
                $npc->base_health       = $mdtNpc->getHealth();
                // MDT doesn't always get this right - don't trust it (Watcher Irideus for example)
                $npc->health_percentage = $npc->health_percentage ?? $mdtNpc->getHealthPercentage();
                $npc->level             = $mdtNpc->getLevel();
                $npc->mdt_scale         = $mdtNpc->getScale();
                $npc->npc_type_id       = NpcType::ALL[$mdtNpc->getCreatureType()] ?? NpcType::UNCATEGORIZED;
                $npc->truesight         = $mdtNpc->getStealthDetect();

                // Save characteristics
                foreach ($mdtNpc->getCharacteristics() as $characteristicName => $enabled) {
                    if (!$enabled) {
                        continue;
                    }

                    /** @var Characteristic|null $characteristic */
                    $characteristic = $characteristicsByName->get($characteristicName);
                    if ($characteristic === null) {
                        $this->log->importNpcsDataFromMDTUnableToFindCharacteristicForNpc($npc->id, $characteristicName);
                        continue;
                    }

                    $npcCharacteristicsAttributes[] = [
                        'npc_id'            => $npc->id,
                        'characteristic_id' => $characteristic->id,
                    ];
                }

                // Save spells
                foreach ($mdtNpc->getSpells() as $spellId => $obj) {
                    if( in_array($spellId, Spell::EXCLUDE_MDT_IMPORT_SPELLS) ) {
                        $this->log->importNpcsDataFromMDTSpellInExcludeList();
                        continue;
                    }

                    $npcSpellsAttributes[sprintf('%s-%s', $npc->id, $spellId)] = [
                        'npc_id'   => $npc->id,
                        'spell_id' => $spellId,
                    ];
                }

                try {
                    $saveResult = $newlyCreated ? $npc->save() : $npc->update();
                    if (!$saveResult) {
                        throw new Exception(sprintf('Unable to save npc %d!', $npc->id));
                    } else if ($newlyCreated) {
                        $this->log->importNpcsDataFromMDTSaveNewNpc($npc->id);
                    }

                    if ($mdtNpc->getCount() > 0 && $newlyCreated) {
                        // For new NPCs go back and create enemy forces for all historical mapping versions
                        $npc->createNpcEnemyForcesForExistingMappingVersions($mdtNpc->getCount());
                    }
                } catch (UniqueConstraintViolationException $exception) {
                    $this->log->importNpcsDataFromMDTNpcNotMarkedForAllDungeons($npc?->id ?? 0);
                } catch (Exception $exception) {
                    $this->log->importNpcsDataFromMDTSaveNpcException($exception);
                }
            }

            // It's easier to delete/insert new ones than try to maintain the IDs which don't really mean anything anyway
            // Clear characteristics/spells for all affected NPCs
            $npcCharacteristicsDeleted = NpcCharacteristic::whereIn('npc_id', $affectedNpcIds)->delete();
            $npcSpellsDeleted          = NpcSpell::whereIn('npc_id', $affectedNpcIds)->delete();

            // Insert new ones
            NpcCharacteristic::insert($npcCharacteristicsAttributes);
            NpcSpell::insert($npcSpellsAttributes);

            $this->log->importNpcsDataFromMDTCharacteristicsAndSpellsUpdate(
                $npcCharacteristicsDeleted,
                count($npcCharacteristicsAttributes),
                $npcSpellsDeleted,
                count($npcSpellsAttributes)
            );
        } finally {
            $this->log->importNpcsDataFromMDTEnd();
        }
    }

    public function importSpellDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        try {
            $this->log->importSpellDataFromMDTStart($dungeon->key);

            $existingSpells = Spell::all()->keyBy('id');

            $spellsAttributes = [];
            foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
                $mdtSpells = $mdtNpc->getSpells();

                foreach ($mdtSpells as $spellId => $spell) {
                    // Ignore spells that we know of - we really only have IDs from MDT, so keep any data that was already there
                    if ($existingSpells->get($spellId) !== null) {
                        continue;
                    }

                    if (in_array($spellId, Spell::EXCLUDE_MDT_IMPORT_SPELLS)) {
                        $this->log->importSpellDataFromMDTSpellInExcludeList();

                        continue;
                    }

                    $spellsAttributes[$spellId] = [
                        'id'             => $spellId,
                        'category'       => sprintf('spells.category.%s', Spell::CATEGORY_UNKNOWN),
                        'cooldown_group' => sprintf('spells.cooldown_group.%s', Spell::COOLDOWN_GROUP_UNKNOWN),
                        'dispel_type'    => Spell::DISPEL_TYPE_UNKNOWN,
                        'icon_name'      => '',
                        'name'           => '',
                        'schools_mask'   => 0,
                        'aura'           => 0,
                        'selectable'     => 0,
                    ];
                }
            }

            if (Spell::insert($spellsAttributes)) {
                $this->log->importSpellDataFromMDTResult(count($spellsAttributes));
            } else {
                $this->log->importSpellDataFromMDTFailed();
            }
        } finally {
            $this->log->importSpellDataFromMDTEnd();
        }
    }

    /**
     * @throws Exception
     */
    private function importDungeon(MDTDungeon $mdtDungeon, Dungeon $dungeon, MappingVersion $newMappingVersion): void
    {
        try {
            $this->log->importDungeonStart();
            $totalCount = $mdtDungeon->getDungeonTotalCount();
            $this->log->importDungeonTotalCounts($mdtDungeon->getMDTDungeonID(), $totalCount['normal'], $totalCount['teeming']);

            if ($dungeon->update([
                    'mdt_id' => $mdtDungeon->getMDTDungeonID(),
                ]) && $newMappingVersion->update([
                    'enemy_forces_required'         => $totalCount['normal'],
                    'enemy_forces_required_teeming' => $totalCount['teeming'],
                ])) {
                $this->log->importDungeonOK();
            } else {
                $this->log->importDungeonFailed();
                throw new Exception(sprintf('Unable to update dungeon %s!', __($dungeon->name)));
            }
        } finally {
            $this->log->importDungeonEnd();
        }
    }

    /**
     * @throws Exception
     */
    private function importNpcs(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        try {
            $this->log->importNpcsStart();

            $this->importNpcsDataFromMDT($mdtDungeon, $dungeon);

            // Get a list of NPCs and update/save them
            $npcs = $dungeon->npcs->keyBy('id');

            foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
                /** @var Npc|null $npc */
                $npc = $npcs->get($mdtNpc->getId());

                if ($npc === null) {
                    $this->log->importNpcsUnableToFindNpc($mdtNpc->getId());

                    continue;
                }

                try {
                    if ($mdtNpc->getCount() > 0) {
                        // Create new enemy forces for this NPC for this new mapping version
                        NpcEnemyForces::create([
                            'mapping_version_id'   => $newMappingVersion->id,
                            'npc_id'               => $npc->id,
                            'enemy_forces'         => $mdtNpc->getCount(),
                            'enemy_forces_teeming' => null,
                        ]);

                        $this->log->importNpcsUpdateExistingNpc($npc->id);
                    }

                    // If shrouded (zul'gamux) update the mapping version to account for that
                    if ($npc->isShrouded()) {
                        $newMappingVersion->update([
                            'enemy_forces_shrouded' => $mdtNpc->getCount(),
                        ]);
                    } else if ($npc->isShroudedZulGamux()) {
                        $newMappingVersion->update([
                            'enemy_forces_shrouded_zul_gamux' => $mdtNpc->getCount(),
                        ]);
                    }
                } catch (Exception $exception) {
                    $this->log->importNpcsDataFromMDTSaveNpcException($exception);
                }
            }
        } finally {
            $this->log->importNpcsEnd();
        }
    }

    /**
     * @return Collection<Enemy>
     */
    private function importEnemies(
        Mappingversion $currentMappingVersion,
        MappingVersion $newMappingVersion,
        MDTDungeon     $mdtDungeon,
        Dungeon        $dungeon,
        bool           $forceImport = false): Collection
    {
        // Get a list of new enemies and save them
        try {
            $this->log->importEnemiesStart();

            $currentEnemies = $currentMappingVersion->enemies->keyBy(static fn(Enemy $enemy) => $enemy->getUniqueKey());

            $enemies = $mdtDungeon->getClonesAsEnemies($newMappingVersion, $dungeon->floors()->active()->get());

            foreach ($enemies as $enemy) {
                if (in_array($enemy->npc_id, self::IGNORE_ENEMY_NPC_IDS)) {
                    $this->log->importEnemiesSkipIgnoredByNpcEnemy($enemy->getUniqueKey());

                    continue;
                }

                // Skip teeming enemies for now - this affix was removed ages ago
                if ($enemy->teeming !== null) {
                    $this->log->importEnemiesSkipTeemingEnemy($enemy->getUniqueKey());

                    continue;
                }

                $enemy->exists = false;
                $enemy->unsetRelations();

                // Not saved in the database
                unset($enemy->npc);
                unset($enemy->id);
                unset($enemy->mdt_npc_index);
                unset($enemy->is_mdt);
                unset($enemy->enemy_id);

                // Is group ID - we handle this later on
                $enemy->enemy_pack_id      = null;
                $enemy->mapping_version_id = $newMappingVersion->id;

                $currentEnemy = $currentEnemies->get($enemy->getUniqueKey());
                if ($currentEnemy instanceof Enemy) {
                    $fields = ['teeming', 'faction', 'required', 'skippable', 'kill_priority'];
                    // We ignore MDT's position - we want to keep agency in the location we place enemies still
                    // since we value VERY MUCH the enemy location being accurate to where they are in-game
                    if (!$forceImport) {
                        $fields = array_merge($fields, ['floor_id', 'lat', 'lng']);
                    }

                    $updatedFields = [];
                    foreach ($fields as $field) {
                        $enemy->$field         = $currentEnemy->$field;
                        $updatedFields[$field] = $currentEnemy->$field;
                    }

                    $this->log->importEnemiesRecoverPropertiesFromExistingEnemy($enemy->getUniqueKey(), $updatedFields);
                } else {
                    $this->log->importEnemiesCannotRecoverPropertiesFromExistingEnemy($enemy->getUniqueKey());
                }

                try {
                    if ($enemy->save()) {
                        $this->log->importEnemiesSaveNewEnemy($enemy->id);
                    } else {
                        throw new Exception(sprintf('Unable to save enemy %d!', $enemy->id));
                    }
                } catch (Exception $exception) {
                    $this->log->importEnemiesSaveNewEnemyException($exception);
                }
            }
        } finally {
            $this->log->importEnemiesEnd();
        }

        return $enemies;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function importEnemyPacks(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon, Collection $savedEnemies): void
    {
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
                /** @var $mdtEnemiesWithGroupsByEnemyPack Collection<Enemy> */
                $mdtEnemiesWithGroupsByEnemyPack = $mdtEnemiesWithGroupsByEnemyPack
                    ->filter(static fn(Enemy $enemy) => $enemy->teeming === null && !in_array($enemy->npc_id, self::IGNORE_ENEMY_NPC_IDS))
                    ->keyBy('id');

                // Enemies without a group - don't import that group, or no enemies assigned to the group
                if (is_null($groupIndex) || $groupIndex === -1 || $mdtEnemiesWithGroupsByEnemyPack->isEmpty()) {
                    continue;
                }

                // We cannot use the enemies from MDT directly since they may contain an incorrect lat/lng
                // We do not re-import the lat/lng from MDT - we allow ourselves to adjust the lat/lng, so we must
                // fetch the adjusted lat/lng by matching enemies with what we actually saved
                // 1. Get a list of unique keys which we must look for in the real enemy list
                $enemiesWithGroupsByEnemyPackUniqueIds = $mdtEnemiesWithGroupsByEnemyPack->map(static fn(Enemy $enemy) => $enemy->getUniqueKey());
                // 2. Find the enemies that were saved in the database by key
                $boundingBoxEnemies = $newMappingVersionEnemies->filter(static fn(Enemy $enemy) => $enemiesWithGroupsByEnemyPackUniqueIds->search($enemy->getUniqueKey()) !== false);

                /** @var Enemy $firstEnemy */
                $firstEnemy = ($boundingBoxEnemies->first() ?? $mdtEnemiesWithGroupsByEnemyPack->first());

                $enemyPack = EnemyPack::create([
                    'mapping_version_id' => $newMappingVersion->id,
                    'floor_id'           => $firstEnemy->floor_id,
                    'group'              => $groupIndex,
                    'teeming'            => null,
                    'faction'            => Faction::FACTION_ANY,
                    'label'              => sprintf('Imported from MDT - group %d', $groupIndex),
                    // 3. Create a new bounding box according to the new enemies lat/lngs
                    'vertices_json'      => json_encode($this->getVerticesBoundingBoxFromEnemies($boundingBoxEnemies)),
                ]);
                if ($enemyPack === null) {
                    throw new Exception('Unable to save enemy pack!');
                }

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
     * @throws Exception
     */
    private function importEnemyPatrols(MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon, Collection $savedEnemies): void
    {
        try {
            $this->log->importEnemyPatrolsStart();

            // Get a list of new enemies and save them
            $mdtNPCs = $mdtDungeon->getMDTNPCs();

            // Pretend the patrol is on the facade floor for correct translations, IF the facade floor is available
            $facadeFloor = $dungeon->getFacadeFloor();

            foreach ($mdtNPCs as $mdtNPC) {
                foreach ($mdtNPC->getRawMdtNpc()['clones'] as $mdtCloneIndex => $mdtNpcClone) {
                    if (!isset($mdtNpcClone['patrol'])) {
                        continue;
                    }

                    if (isset($mdtNpcClone['teeming'])) {
                        continue;
                    }

                    $savedEnemy = $this->findSavedEnemyFromCloneEnemy($savedEnemies, $mdtNPC->getId(), $mdtCloneIndex);
                    $this->log->importEnemyPatrolsEnemyHasPatrol($savedEnemy->getUniqueKey());

                    if (empty($mdtNpcClone['patrol'])) {
                        $this->log->importEnemyPatrolsFoundPatrolIsEmpty($savedEnemy->getUniqueKey());

                        continue;
                    }

                    $vertices = [];
                    foreach ($mdtNpcClone['patrol'] as $xy) {
                        $latLng     = Conversion::convertMDTCoordinateToLatLng($xy, $facadeFloor ?? $savedEnemy->floor);
                        $latLng     = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);
                        $vertices[] = $latLng->toArray();
                    }

                    // MDT automatically closes up the patrol which I don't, so correct for this (confirmed by Nnoggie)
                    $vertices[] = $vertices[0];

                    // Polyline
                    $polyLine = Polyline::create([
                        'model_id'       => -1,
                        'model_class'    => EnemyPatrol::class,
                        'color'          => '#003280',
                        'color_animated' => null,
                        'weight'         => 2,
                        'vertices_json'  => json_encode($vertices),
                    ]);
                    if ($polyLine !== null) {
                        $this->log->importEnemyPatrolsSaveNewPolyline($polyLine->id);
                    } else {
                        throw new Exception(sprintf('Unable to save polyline!'));
                    }

                    // Enemy patrols
                    $enemyPatrol = EnemyPatrol::create([
                        'mapping_version_id' => $newMappingVersion->id,
                        'floor_id'           => $savedEnemy->floor_id,
                        'polyline_id'        => $polyLine->id,
                        'mdt_npc_id'         => $mdtNPC->getId(),
                        'mdt_id'             => $mdtCloneIndex,
                        'teeming'            => null,
                        'faction'            => Faction::FACTION_ANY,
                    ]);
                    if ($enemyPatrol !== null) {
                        $this->log->importEnemyPatrolsSaveNewEnemyPatrol($enemyPatrol->id);
                    } else {
                        throw new Exception(sprintf('Unable to save enemy patrol!'));
                    }

                    // Couple polyline to enemy patrol
                    $polyLineSaveResult = $polyLine->update(['model_id' => $enemyPatrol->id]);
                    if ($polyLineSaveResult) {
                        $this->log->importEnemyPatrolsCoupleEnemyPatrolToPolyline($enemyPatrol->id, $polyLine->id);
                    } else {
                        throw new Exception(sprintf('Unable to save polyline!'));
                    }

                    // Couple enemy/enemies to enemy patrol
                    if ($savedEnemy->enemy_pack_id !== null) {
                        $enemyUpdateResult = Enemy::where('enemy_pack_id', $savedEnemy->enemy_pack_id)->update(['enemy_patrol_id' => $enemyPatrol->id]);
                    } else {
                        $enemyUpdateResult = $savedEnemy->update(['enemy_patrol_id' => $enemyPatrol->id]);
                    }

                    if ($enemyUpdateResult) {
                        $this->log->importEnemyPatrolsCoupleEnemiesToEnemyPatrol($enemyPatrol->id);
                    } else {
                        throw new Exception(sprintf('Unable to update enemy to have attached patrol!'));
                    }
                }
            }
        } finally {
            $this->log->importEnemyPatrolsEnd();
        }
    }

    /**
     * @throws Exception
     */
    private function importDungeonFloorSwitchMarkers(MappingVersion $currentMappingVersion, MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        try {
            $this->log->importDungeonFloorSwitchMarkersStart();
            $mdtMapPOIs = $mdtDungeon->getMDTMapPOIs();

            // So because of the linked_floor_switch_id we cannot re-import dungeon floor switches
            // We cannot for sure map the floor switches between different versions to one another
            // We could use coordinates but if they change it's iffy.
            // They also don't change between mapping versions, it's not really something Blizzard _can_ change

            if ($currentMappingVersion->dungeonFloorSwitchMarkers->isEmpty() && $mdtMapPOIs->isNotEmpty()) {
                $this->log->importDungeonFloorSwitchMarkersImportFromMDT();
                foreach ($mdtMapPOIs as $mdtMapPOI) {
                    if ($mdtMapPOI->getType() !== MDTMapPOI::TYPE_MAP_LINK) {
                        continue;
                    }

                    $floor = $this->findFloorByMdtSubLevel($dungeon, $mdtMapPOI->getSubLevel());

                    $latLng = Conversion::convertMDTCoordinateToLatLng(['x' => $mdtMapPOI->getX(), 'y' => $mdtMapPOI->getY()], $floor);
                    $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);

                    $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::create(array_merge([
                        'mapping_version_id' => $newMappingVersion->id,
                        'floor_id'           => $floor->id,
                        'target_floor_id'    => $this->findFloorByMdtSubLevel($dungeon, $mdtMapPOI->getTarget())->id,
                    ], $latLng->toArray()));
                    if ($dungeonFloorSwitchMarker !== null) {
                        $this->log->importDungeonFloorSwitchMarkersNewDungeonFloorSwitchMarkerOK(
                            $dungeonFloorSwitchMarker->id,
                            $dungeonFloorSwitchMarker->floor_id,
                            $dungeonFloorSwitchMarker->target_floor_id,
                        );
                    } else {
                        throw new Exception('Unable to save dungeon floor switch marker!');
                    }
                }
            } else {
                $this->log->importDungeonFloorSwitchMarkersHaveExistingFloorSwitchMarkers(
                    $currentMappingVersion->dungeonFloorSwitchMarkers->count()
                );
            }
        } finally {
            $this->log->importDungeonFloorSwitchMarkersEnd();
        }
    }

    private function findSavedEnemyFromCloneEnemy(Collection $savedEnemies, int $npcId, int $mdtId): Enemy
    {
        return $savedEnemies->firstOrFail(static fn(Enemy $enemy) => $enemy->npc_id === $npcId && $enemy->mdt_id === $mdtId);
    }

    public function findFloorByMdtSubLevel(Dungeon $dungeon, int $mdtSubLevel): Floor
    {
        $activeFloors = $dungeon->floors()->active()->get();

        // First check for mdt_sub_level, if that isn't found just match on our own index
        return $activeFloors->first(static fn(Floor $floor) => $floor->mdt_sub_level === $mdtSubLevel) ?? $activeFloors->first(static fn(Floor $floor) => $floor->index === $mdtSubLevel);
    }

    /**
     * Get a bounding box which encompasses all passed enemies
     *
     * @param Collection<Enemy> $enemies
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
        $minLat  -= $padding;
        $minLng  -= $padding;
        $maxLat  += $padding;
        $maxLng  += $padding;

        // Create a box
        return [
            ['lat' => $minLat, 'lng' => $minLng],
            ['lat' => $maxLat, 'lng' => $minLng],
            ['lat' => $maxLat, 'lng' => $maxLng],
            ['lat' => $minLat, 'lng' => $maxLng],
        ];
    }
}
