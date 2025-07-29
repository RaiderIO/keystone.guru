<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Logic\MDT\Entity\MDTNpc;
use App\Logic\MDT\Entity\MDTPatrol;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcHealth;
use App\Models\Npc\NpcSpell;
use App\Models\Npc\NpcType;
use App\Models\Polyline;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
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

    /** @var array Do not import data from these NPC IDs */
    private const IGNORE_NPC_DATA_NPC_IDS = [
        // Priory of the Sacred Flame - 3 mini bosses where MDT has high health values - they mess up auto map sizing based on health
        211289,
        211290,
        211291,
    ];

    private const IGNORE_ENEMY_DISTANCE_CHECK_NPC_IDS = [
        // Darkflame Cleft, The Darkness - we move it to an entirely different area so please ignore this check
        208747,
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

        $currentMappingVersion = $dungeon->getCurrentMappingVersion();
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
                $this->importMapPOIs($currentMappingVersion, $newMappingVersion, $mdtDungeon, $dungeon);
            } finally {
                $this->log->importMappingVersionFromMDTEnd();
            }

            return $newMappingVersion;
        } else {
            $this->log->importDungeonMappingVersionFromMDTNoChangeDetected($dungeon->key, $latestMdtMappingHash);

            return $currentMappingVersion;
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
            $existingNpcs = $dungeon->npcs()->with('npcSpells')->get()->keyBy('id');

            $characteristicsByName = Characteristic::all()->mapWithKeys(function (Characteristic $characteristic) {
                return [__($characteristic->name, [], 'en_US') => $characteristic];
            });

            $npcsUpdated                  = $npcsInserted = 0;
            $npcCharacteristicsAttributes = [];
            $npcSpellsAttributes          = [];
            $npcDungeonsAttributes        = [];
            $affectedNpcIds               = [];
            $gameVersionRetail            = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

            /** @var Npc|null $npc */
            foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
                if (in_array($mdtNpc->getId(), self::IGNORE_NPC_DATA_NPC_IDS)) {
                    $this->log->importNpcsDataFromMDTIgnoreNpc($mdtNpc->getId());
                    continue;
                }

                $affectedNpcIds[] = $mdtNpc->getId();

                $npc = $existingNpcs->get($mdtNpc->getId());

                if ($newlyCreated = ($npc === null)) {
                    $npc = new Npc();
                }

                $npc->id = $mdtNpc->getId();
                // Allow manual override to -1
                $npc->display_id        = $mdtNpc->getDisplayId();
                $npc->encounter_id      = $mdtNpc->getEncounterId();
                $npc->classification_id ??= NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE];
                $npc->name              = $mdtNpc->getName();
                $npc->level             = $mdtNpc->getLevel();
                $npc->mdt_scale         = $mdtNpc->getScale();
                $npc->npc_type_id       = NpcType::ALL[$mdtNpc->getCreatureType()] ?? NpcType::UNCATEGORIZED;
                $npc->truesight         = $mdtNpc->getStealthDetect();

                // If no dungeons are assigned, OR if the current dungeon is not part of the list
                if (!$npc->exists) {
                    $npc->load('npcDungeons');

                    if ($npc->npcDungeons->filter(function (NpcDungeon $npcDungeon) use ($dungeon) {
                        // Check if this NPC is already associated with this dungeon
                        return $npcDungeon->dungeon_id === $dungeon->id;
                    })->isEmpty()) {
                        $npcDungeonsAttributes[] = [
                            'npc_id'     => $npc->id,
                            'dungeon_id' => $dungeon->id,
                        ];
                    }
                }

                // Save/update health
                $npcHealth = $npc->getHealthByGameVersion($gameVersionRetail);
                if ($npcHealth === null) {
                    $npcHealth = new NpcHealth([
                        'npc_id'          => $npc->id,
                        'game_version_id' => $gameVersionRetail->id,
                        'health'          => $mdtNpc->getHealth(),
                    ]);
                }
                $npcHealth->health = $mdtNpc->getHealth();
                // MDT doesn't always get this right - don't trust it (Watcher Irideus for example)
                $npcHealth->percentage = $npc->health_percentage ?? $mdtNpc->getHealthPercentage();
                $npcHealth->save();

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

                // Save spells that we don't know of yet
                foreach ($mdtNpc->getSpells() as $spellId => $obj) {
                    if (in_array($spellId, Spell::EXCLUDE_MDT_IMPORT_SPELLS)) {
                        $this->log->importNpcsDataFromMDTSpellInExcludeList();
                        continue;
                    }

                    // Check if it's already associated
                    foreach ($npc->npcSpells as $npcSpell) {
                        if ($npcSpell->spell_id === $spellId) {
                            // It is, don't save the attributes
                            continue 2;
                        }
                    }

                    $npcSpellsAttributes[sprintf('%s-%s', $npc->id, $spellId)] = [
                        'npc_id'   => $npc->id,
                        'spell_id' => $spellId,
                    ];
                }

                try {
                    if ($newlyCreated) {
                        $saveResult = $npc->save();
                        $npcsInserted++;
                    } else {
                        $saveResult = $npc->update();
                        $npcsUpdated++;
                    }

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
            // Do not delete existing spells - we're only interested in new ones
//            $npcSpellsDeleted          = NpcSpell::whereIn('npc_id', $affectedNpcIds)->delete();
            // Do not delete existing dungeons - we're only interested in new ones
//            $npcDungeonsDeleted = NpcDungeon::whereIn('npc_id', $affectedNpcIds)->delete();

            // Insert new ones
            NpcCharacteristic::insert($npcCharacteristicsAttributes);
            NpcSpell::insert($npcSpellsAttributes);
            NpcDungeon::insert($npcDungeonsAttributes);

            $this->log->importNpcsDataFromMDTCharacteristicsAndSpellsUpdate(
                $npcsUpdated,
                $npcsInserted,
                $npcCharacteristicsDeleted,
                count($npcCharacteristicsAttributes),
                0,
                count($npcSpellsAttributes),
                0,
                count($npcDungeonsAttributes)
            );
        } finally {
            $this->log->importNpcsDataFromMDTEnd();
        }
    }

    public function importSpellDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        try {
            $this->log->importSpellDataFromMDTStart($dungeon->key);

            $existingSpells = Spell::with('spellDungeons')->get()->keyBy('id');

            $spellsAttributes        = [];
            $spellDungeonsAttributes = [];
            foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
                $mdtSpells = $mdtNpc->getSpells();

                foreach ($mdtSpells as $spellId => $spell) {
                    /** @var Spell $existingSpell */
                    $existingSpell = $existingSpells->get($spellId);
                    // Ignore spells that we know of - we really only have IDs from MDT, so keep any data that was already there
                    if ($existingSpell !== null) {
                        if (!$existingSpell->isAssignedDungeon($dungeon)) {
                            // Assign to dungeon
                            $spellDungeonsAttributes[sprintf('%d-%d', $spellId, $dungeon->id)] = [
                                'spell_id'   => $existingSpell->id,
                                'dungeon_id' => $dungeon->id,
                            ];
                        }
                        continue;
                    }

                    if (in_array($spellId, Spell::EXCLUDE_MDT_IMPORT_SPELLS)) {
                        $this->log->importSpellDataFromMDTSpellInExcludeList();

                        continue;
                    }

                    $spellsAttributes[$spellId] = [
                        'id'             => $spellId,
                        'category'       => sprintf('spellcategory.%s', Spell::CATEGORY_UNKNOWN),
                        'cooldown_group' => sprintf('spellcooldowngroup.%s', Spell::COOLDOWN_GROUP_UNKNOWN),
                        'dispel_type'    => Spell::DISPEL_TYPE_UNKNOWN,
                        'icon_name'      => '',
                        'name'           => '',
                        'schools_mask'   => 0,
                        'aura'           => 0,
                        'selectable'     => 0,
                    ];

                    // Couple the spell to this dungeon
                    $spellDungeonsAttributes[sprintf('%d-%d', $spellId, $dungeon->id)] = [
                        'spell_id'   => $spellId,
                        'dungeon_id' => $dungeon->id,
                    ];
                }
            }

            if (Spell::insert($spellsAttributes) && SpellDungeon::insert($spellDungeonsAttributes)) {
                $this->log->importSpellDataFromMDTResult(count($spellsAttributes), count($spellDungeonsAttributes));
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

            // Get a list of NPCs and update/save them (re-fetch the list to include any new NPCs)
            $npcs = $dungeon->npcs()->get()->keyBy('id');

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

            $currentEnemies = $currentMappingVersion
                ->enemies()
                ->with('floor')
                ->get()
                ->keyBy(static fn(Enemy $enemy) => $enemy->getUniqueKey());

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
                $mdtEnemy->enemy_pack_id      = null;
                $mdtEnemy->mapping_version_id = $newMappingVersion->id;

                $existingEnemy = $currentEnemies->get($mdtEnemy->getUniqueKey());
                if ($existingEnemy instanceof Enemy) {
                    $fields = ['teeming', 'faction', 'required', 'skippable', 'kill_priority'];
                    // We ignore MDT's position - we want to keep agency in the location we place enemies still
                    // since we value VERY MUCH the enemy location being accurate to where they are in-game
                    if (!$forceImport) {
                        $fields[] = 'floor_id';

                        if (!in_array($mdtEnemy->npc_id, self::IGNORE_ENEMY_DISTANCE_CHECK_NPC_IDS) &&
                            ($distance = $this->coordinatesService->distanceIngameXY(
                                $this->coordinatesService->calculateIngameLocationForMapLocation($existingEnemy->getLatLng()),
                                $this->coordinatesService->calculateIngameLocationForMapLocation($mdtEnemy->getLatLng())
                            )) > 150) {
                            $this->log->importEnemiesDistanceTooLargeNotTransferringExistingEnemyLatLng($mdtEnemy->getUniqueKey(), $distance);
                        } else {
                            // Ok, copy over lat/lng
                            $fields = array_merge($fields, ['lat', 'lng']);
                        }
                    }

                    $updatedFields = [];
                    foreach ($fields as $field) {
                        $mdtEnemy->$field      = $existingEnemy->$field;
                        $updatedFields[$field] = $existingEnemy->$field;
                    }

                    // Special case - if we manually assigned the MDT placeholder, we would want to migrate that over as well.
                    // But all other seasonal types can be adjusted by MDT and we copy them back over.
                    if ($existingEnemy->seasonal_type === Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER) {
                        $mdtEnemy->seasonal_type        = Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER;
                        $updatedFields['seasonal_type'] = Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER;
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
                        throw new Exception('Unable to save polyline!');
                    }

                    // MDT Polyline
                    $mdtPolyLine = Polyline::create([
                        'model_id'       => -1,
                        'model_class'    => MDTPatrol::class,
                        'color'          => '#003280',
                        'color_animated' => null,
                        'weight'         => 2,
                        // Save the direct X and Y coordinates as lat/lng so we can echo it back later exactly
                        // This polyline is not meant to be used for display, but rather to be used for MDT
                        'vertices_json'  => json_encode(array_values(array_map(fn(array $v) => [
                            'lat' => $v['y'],
                            'lng' => $v['x'],
                        ], $mdtNpcClone['patrol']))),
                    ]);
                    if ($mdtPolyLine !== null) {
                        $this->log->importEnemyPatrolsSaveNewMdtPolyline($mdtPolyLine->id);
                    } else {
                        throw new Exception('Unable to save MDT polyline!');
                    }

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
                    if ($enemyPatrol !== null) {
                        $this->log->importEnemyPatrolsSaveNewEnemyPatrol($enemyPatrol->id);
                    } else {
                        throw new Exception('Unable to save enemy patrol!');
                    }

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
        } finally {
            $this->log->importEnemyPatrolsEnd();
        }
    }

    /**
     * @throws Exception
     */
    private function importMapPOIs(MappingVersion $currentMappingVersion, MappingVersion $newMappingVersion, MDTDungeon $mdtDungeon, Dungeon $dungeon): void
    {
        try {
            $this->log->importMapPOIsStart();
            $mdtMapPOIs = $mdtDungeon->getMDTMapPOIs();

            if ($mdtMapPOIs->isNotEmpty()) {
                $this->log->importMapPOIsMDTHasMapPOIs();

                $mapIconTypeMapping = [
                    MDTMapPOI::TYPE_GRAVEYARD               => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GRAVEYARD],
                    MDTMapPOI::TYPE_DUNGEON_ENTRANCE        => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START],
                    MDTMapPOI::TYPE_PRIORY_ITEM             => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_PRIORY_BLESSING_OF_THE_SACRED_FLAME],
                    MDTMapPOI::TYPE_FLOODGATE_ITEM          => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_FLOODGATE_WEAPONS_STOCKPILE_EXPLOSION],
                    MDTMapPOI::TYPE_ECO_DOME_AL_DANI_ITEM_1 => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_ECO_DOME_AL_DANI_SHATTER_CONDUIT],
                    MDTMapPOI::TYPE_ECO_DOME_AL_DANI_ITEM_2 => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_ECO_DOME_AL_DANI_DISRUPTION_GRENADE],
                    MDTMapPOI::TYPE_ECO_DOME_AL_DANI_ITEM_3 => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_ECO_DOME_AL_DANI_KARESHI_SURGE],
                    MDTMapPOI::TYPE_GENERAL_NOTE            => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
                ];

                foreach ($mdtMapPOIs as $mdtMapPOI) {
                    $floor = $this->findFloorByMdtSubLevel($dungeon, $mdtMapPOI->getSubLevel());

                    $latLng = Conversion::convertMDTCoordinateToLatLng(['x' => $mdtMapPOI->getX(), 'y' => $mdtMapPOI->getY()], $floor);
                    $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);

                    if (isset($mapIconTypeMapping[$mdtMapPOI->getType()])) {
                        $existingMapIcon = $currentMappingVersion->getMapIconNearLocation($latLng, $mapIconTypeMapping[$mdtMapPOI->getType()]);
                        if ($existingMapIcon === null) {
                            $mapIcon = MapIcon::create(array_merge([
                                'mapping_version_id' => $newMappingVersion->id,
                                'map_icon_type_id'   => $mapIconTypeMapping[$mdtMapPOI->getType()],
                            ], $latLng->toArrayWithFloor()));

                            $this->log->importMapPOIsCreatedNewMapIcon(
                                $mapIcon->id,
                                $mapIcon->floor_id,
                                $mapIcon->map_icon_type_id,
                            );
                        } else {
                            $this->log->importMapPOIsMapIconAlreadyExists(
                                $existingMapIcon->id,
                                $latLng->toArray(),
                                $mdtMapPOI->getType()
                            );
                        }
                    } else if ($mdtMapPOI->getType() === MDTMapPOI::TYPE_MAP_LINK) {
                        // So because of the linked_floor_switch_id we cannot re-import dungeon floor switches
                        // We cannot for sure map the floor switches between different versions to one another
                        // We could use coordinates but if they change it's iffy.
                        // They also don't change between mapping versions, it's not really something Blizzard _can_ change
                        if ($currentMappingVersion->dungeonFloorSwitchMarkers->isEmpty()) {
                            $floor = $this->findFloorByMdtSubLevel($dungeon, $mdtMapPOI->getSubLevel());

                            $latLng = Conversion::convertMDTCoordinateToLatLng(['x' => $mdtMapPOI->getX(), 'y' => $mdtMapPOI->getY()], $floor);
                            $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);

                            $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::create(array_merge([
                                'mapping_version_id' => $newMappingVersion->id,
                                'floor_id'           => $floor->id,
                                'target_floor_id'    => $this->findFloorByMdtSubLevel($dungeon, $mdtMapPOI->getTarget())->id,
                            ], $latLng->toArray()));
                            if ($dungeonFloorSwitchMarker !== null) {
                                $this->log->importMapPOIsNewDungeonFloorSwitchMarkerOK(
                                    $dungeonFloorSwitchMarker->id,
                                    $dungeonFloorSwitchMarker->floor_id,
                                    $dungeonFloorSwitchMarker->target_floor_id,
                                );
                            } else {
                                throw new Exception('Unable to save dungeon floor switch marker!');
                            }
                        } else {
                            $this->log->importMapPOIsHaveExistingFloorSwitchMarkers(
                                $currentMappingVersion->dungeonFloorSwitchMarkers->count()
                            );
                        }
                    }
                }
            }
        } finally {
            $this->log->importMapPOIsEnd();
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
