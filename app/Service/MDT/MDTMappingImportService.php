<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Logic\MDT\Entity\MDTMapPOIType;
use App\Logic\MDT\Entity\MDTNpc;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonStart;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\Exceptions\MDTMappingImportPartialFailureException;
use App\Service\MDT\Exceptions\MDTMappingNpcSetReplacedException;
use App\Service\MDT\Exceptions\MDTMappingPendingAcceptanceException;
use App\Service\MDT\Import\Mapping\EnemyImporter;
use App\Service\MDT\Import\Mapping\EnemyPackImporter;
use App\Service\MDT\Import\Mapping\EnemyPatrolImporter;
use App\Service\MDT\Import\Mapping\NpcImporter;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use Exception;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use Str;
use Throwable;

class MDTMappingImportService implements MDTMappingImportServiceInterface
{
    /**
     * An import must keep at least this fraction of the previous mapping version's NPCs, or it is refused.
     * The lowest legitimate transition observed across all 307 in the database was 59%, so this leaves 9
     * percentage points of headroom (#3995).
     */
    public const float NPC_SET_OVERLAP_MINIMUM = 0.5;

    private readonly NpcImporter $npcImporter;

    private readonly EnemyImporter $enemyImporter;

    private readonly EnemyPackImporter $enemyPackImporter;

    private readonly EnemyPatrolImporter $enemyPatrolImporter;

    public function __construct(
        private readonly CacheServiceInterface                   $cacheService,
        private readonly CoordinatesServiceInterface             $coordinatesService,
        private readonly MDTAddonVersionServiceInterface         $mdtAddonVersionService,
        private readonly MDTMappingImportServiceLoggingInterface $log,
    ) {
        $this->npcImporter         = new NpcImporter($this->log);
        $this->enemyImporter       = new EnemyImporter($this->coordinatesService, $this->log);
        $this->enemyPackImporter   = new EnemyPackImporter($this->log);
        $this->enemyPatrolImporter = new EnemyPatrolImporter($this->coordinatesService, $this->log);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function importMappingVersionFromMDT(
        MappingServiceInterface $mappingService,
        Dungeon                 $dungeon,
        ?GameVersion            $gameVersion = null,
        bool                    $forceImport = false,
    ): MappingVersion {
        $latestMdtMappingHash = $this->getMDTMappingHash($dungeon);

        $gameVersion ??= GameVersion::getDefaultGameVersion();

        // Scoped to $gameVersion only - unlike Dungeon::getCurrentMappingVersion(), this does NOT fall back to
        // the parent's or an ambient/unrelated game version's mapping version when the dungeon has none for
        // $gameVersion yet (e.g. its first-ever import for a newly-added game version). A null here is a real,
        // valid case: the downstream clone/import calls below all treat it as "nothing to carry over from".
        /** @var MappingVersion|null $currentMappingVersion */
        $currentMappingVersion = $dungeon->reloadMappingVersions()->mappingVersions()->where('game_version_id', $gameVersion->id)->first();
        if ($forceImport || $currentMappingVersion === null || $currentMappingVersion->mdt_mapping_hash !== $latestMdtMappingHash) {
            $this->log->importMappingVersionFromMDTMappingChanged($currentMappingVersion?->mdt_mapping_hash, $latestMdtMappingHash);

            // The current mapping version is one of our own corrections awaiting MDT acceptance, and MDT's
            // mapping has changed - most likely because they accepted it. Creating a new mapping version here
            // would bump v7 (MDT) -> v8 (ours) -> v9 (MDT, identical to v8) and invalidate every route on v8
            // for no actual mapping change, so accepting onto v8 is left to mdt:acceptmapping (#4281).
            if (!$forceImport && ($currentMappingVersion?->mdt_changes_pending ?? false)) { // @phpstan-ignore nullsafe.neverNull
                $this->log->importMappingVersionFromMDTPendingAcceptance($dungeon->key, $currentMappingVersion->version);

                throw new MDTMappingPendingAcceptanceException($dungeon->key, $currentMappingVersion->version);
            }

            // Before anything is created: refuse outright if MDT is handing us a different dungeon's NPCs
            // (#3995). Checking here rather than mid-import means no mapping version is created and deleted
            // again, and mdt_mapping_hash is never touched - the next run simply retries.
            $this->assertMDTNpcSetIsPlausible($dungeon, $currentMappingVersion, $forceImport);

            $newMappingVersion = $mappingService->createNewMappingVersionFromMDTMapping($dungeon, $gameVersion, $currentMappingVersion);
            $this->log->importMappingVersionFromMDTCreateMappingVersion($newMappingVersion->version, $newMappingVersion->id);

            $mdtDungeon = new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeon);

            /** @var array<int, Exception> $importFailures NPC/enemy saves that were individually caught and
             *  logged rather than propagated, so the loop kept going for the remaining items (#3755) */
            $importFailures = [];

            try {
                $this->log->importMappingVersionFromMDTStart($dungeon->id);

                // Enemies are re-created from MDT below, so their checkpoint membership has to be re-linked by
                // hand - which is why the checkpoints are cloned here rather than as part of the mapping
                // version's contents
                $enemyForcesCheckpointIdMapping = $mappingService->copyEnemyForcesCheckpointsToMappingVersion(
                    $currentMappingVersion,
                    $newMappingVersion,
                );

                $this->importDungeon($mdtDungeon, $dungeon, $newMappingVersion);
                $this->importNpcs($newMappingVersion, $mdtDungeon, $dungeon, $gameVersion, $importFailures);
                $enemies = $this->enemyImporter->importEnemies(
                    $currentMappingVersion,
                    $newMappingVersion,
                    $mdtDungeon,
                    $dungeon,
                    $enemyForcesCheckpointIdMapping,
                    $forceImport,
                    $importFailures,
                );
                $this->enemyPackImporter->importEnemyPacks($newMappingVersion, $mdtDungeon, $dungeon, $enemies);
                $this->enemyPatrolImporter->importEnemyPatrols($currentMappingVersion, $newMappingVersion, $mdtDungeon, $dungeon, $enemies);
                $this->importMapPOIs($currentMappingVersion, $newMappingVersion, $mdtDungeon, $dungeon);

                if (!empty($importFailures)) {
                    // Every step above ran, but one or more individual NPCs/enemies were silently dropped
                    // along the way - the mapping version is incomplete even though nothing propagated to
                    // this try block on its own, so it must not be treated as a success (#3755).
                    throw new MDTMappingImportPartialFailureException($importFailures);
                }

                // Only stamp the hash once every step above actually succeeded (#3737) - a mapping version
                // recorded with the hash of MDT data it never finished importing would look up to date to
                // every subsequent run and be skipped forever ("no change detected"), leaving the dungeon
                // stuck on a half-built "current" mapping version.
                $newMappingVersion->update(['mdt_mapping_hash' => $latestMdtMappingHash]);
            } catch (Throwable $throwable) {
                // The partially-imported mapping version is not safe to leave behind as the dungeon's
                // current one - cascading delete removes whatever enemies/packs/etc. made it in before the
                // failure, and the next run retries against the still-null hash instead of silently no-oping.
                $this->log->importMappingVersionFromMDTDeletePartialMappingVersion($newMappingVersion->version, $newMappingVersion->id, $throwable);

                $newMappingVersion->delete();

                throw $throwable;
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
    public function acceptMDTMappingForPendingMappingVersion(
        Dungeon      $dungeon,
        ?GameVersion $gameVersion = null,
        bool         $force = false,
    ): MappingVersion {
        $gameVersion ??= GameVersion::getDefaultGameVersion();

        /** @var MappingVersion|null $currentMappingVersion */
        $currentMappingVersion = $dungeon->reloadMappingVersions()->mappingVersions()->where('game_version_id', $gameVersion->id)->first();

        if ($currentMappingVersion === null) {
            throw new Exception(sprintf('%s has no mapping version for game version %s', $dungeon->key, $gameVersion->key));
        }

        if (!$currentMappingVersion->mdt_changes_pending) {
            throw new Exception(sprintf(
                '%s\'s current mapping version (v%d) is not awaiting MDT acceptance - there is nothing to accept.',
                $dungeon->key,
                $currentMappingVersion->version,
            ));
        }

        $latestMdtMappingHash = $this->getMDTMappingHash($dungeon);

        // An unchanged hash means MDT has shipped nothing since this mapping version was made, so it cannot
        // have accepted our changes yet - clearing the flag would mark a mapping that still diverges as
        // MDT-matching, and MDT strings would start importing onto it (#4280). --force is the deliberate
        // override for the case where the operator knows better (e.g. MDT changed something the hash does
        // not cover).
        if (!$force && $currentMappingVersion->mdt_mapping_hash === $latestMdtMappingHash) {
            throw new Exception(sprintf(
                'MDT\'s mapping for %s has not changed since v%d was created (hash %s), so it cannot have accepted ' .
                'our changes yet. Re-run with --force to accept anyway.',
                $dungeon->key,
                $currentMappingVersion->version,
                $latestMdtMappingHash,
            ));
        }

        $this->log->acceptMDTMappingForPendingMappingVersion(
            $dungeon->key,
            $currentMappingVersion->version,
            $currentMappingVersion->mdt_mapping_hash,
            $latestMdtMappingHash,
        );

        // Note that MDT's mapping is deliberately NOT re-imported into it: the whole point is that the two
        // now agree, and a re-import would drop anything of ours MDT does not carry (hand-authored packs,
        // enemy forces checkpoints). Verification that they really do agree is the operator's, via the MDT
        // cross-check tests (MDTNpcMappingCoverageTest, CoordinatesServiceTest).
        $currentMappingVersion->update([
            'mdt_mapping_hash'    => $latestMdtMappingHash,
            'mdt_addon_version'   => $this->mdtAddonVersionService->getCurrentAddonVersion(),
            'mdt_changes_pending' => false,
        ]);

        return $currentMappingVersion;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getMDTMappingHash(Dungeon $dungeon): string
    {
        $mdtDungeon = new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeon);

        // Only the npcs/floorSwitchMarkers payloads are read from Lua tables whose hash-part key order is
        // unstable across VM instances (#4110) - canonicalizing each independently, rather than wrapping this
        // whole literal array in one canonicalizeForHash() call, keeps the always-stable top-level key order
        // ('counts', 'npcs', 'floorSwitchMarkers') untouched, so the fix doesn't churn every dungeon's hash.
        return md5(
            json_encode([
                'counts'             => $mdtDungeon->getDungeonTotalCount(),
                'npcs'               => self::canonicalizeForHash($mdtDungeon->getMDTNPCs()->toArray()),
                'floorSwitchMarkers' => self::canonicalizeForHash($mdtDungeon->getMDTMapPOIs()->toArray()),
            ]),
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getUnhandledMapPOIs(Dungeon $dungeon): Collection
    {
        return new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeon)
            ->getMDTMapPOIs()
            ->filter(static fn(MDTMapPOI $mdtMapPOI): bool => Conversion::isMDTMapPOIUnhandled($mdtMapPOI))
            ->values();
    }

    public function importNpcsDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon, GameVersion $gameVersion, array &$failures = []): void
    {
        $this->npcImporter->importNpcsDataFromMDT($mdtDungeon, $dungeon, $gameVersion, $failures);
    }

    /**
     * @param  array<int, Exception> $failures Appended with NPC saves that failed - see
     *                                         importNpcsDataFromMDT()'s $failures parameter.
     * @throws Exception
     */
    private function importNpcs(
        MappingVersion $newMappingVersion,
        MDTDungeon     $mdtDungeon,
        Dungeon        $dungeon,
        GameVersion    $gameVersion,
        array          &          $failures,
    ): void {
        try {
            $this->log->importNpcsStart();

            $this->importNpcsDataFromMDT($mdtDungeon, $dungeon, $gameVersion, $failures);

            $this->npcImporter->importNpcEnemyForces($newMappingVersion, $mdtDungeon, $dungeon, $failures);
        } finally {
            $this->log->importNpcsEnd();
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
    private function importMapPOIs(
        ?MappingVersion $currentMappingVersion,
        MappingVersion  $newMappingVersion,
        MDTDungeon      $mdtDungeon,
        Dungeon         $dungeon,
    ): void {
        try {
            $this->log->importMapPOIsStart();
            $mdtMapPOIs = $mdtDungeon->getMDTMapPOIs();

            // Whether $newMappingVersion already has floor switch markers of its own - either cloned from
            // $currentMappingVersion by copyMappingVersionContentsToDungeon(), or (#3757/#3762) cloned from a
            // prior game version's facade-source mapping version by
            // MappingService::createNewMappingVersionFromMDTMapping() on a genuinely first-ever import (where
            // $currentMappingVersion is null). Querying $newMappingVersion directly - rather than inspecting
            // $currentMappingVersion, which is null in exactly the case that now pre-populates markers - is
            // what keeps this from creating duplicates on top of a first-ever import's cloned markers.
            $newMappingVersionHasDungeonFloorSwitchMarkers = $newMappingVersion->dungeonFloorSwitchMarkers()->exists();

            // We place the dungeon's start marker where the party actually starts running, which is rarely where MDT
            // draws its DungeonEntrance POI - so matching on position (the check every other map icon type uses)
            // finds nothing and adds MDT's on top of ours. One start marker anywhere in the mapping version is enough
            // to mean we already have one.
            $newMappingVersionHasDungeonStart = $newMappingVersion->dungeonStarts()->exists();

            if ($mdtMapPOIs->isNotEmpty()) {
                $this->log->importMapPOIsMDTHasMapPOIs();

                $genericItemMapIconTypeIds = $this->getGenericItemMapIconTypeIds();
                $this->deleteClonedGenericItemMapIcons($newMappingVersion, $genericItemMapIconTypeIds);

                foreach ($mdtMapPOIs as $mdtMapPOI) {
                    $floor = $this->findFloorByMdtSubLevel($dungeon, $newMappingVersion, $mdtMapPOI->getSubLevel());

                    $latLng = Conversion::convertMDTCoordinateToLatLng([
                        'x' => $mdtMapPOI->getX(),
                        'y' => $mdtMapPOI->getY(),
                    ], $floor);
                    $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);

                    $mapIconTypeKey = Conversion::convertMDTMapPOIToMapIconTypeKey($mdtMapPOI);

                    if ($mdtMapPOI->getType() === MDTMapPOIType::DungeonEntrance) {
                        if ($newMappingVersionHasDungeonStart) {
                            $this->log->importMapPOIsHaveExistingDungeonStart($latLng->toArray());

                            continue;
                        }

                        $dungeonStart = DungeonStart::create(array_merge([
                            'mapping_version_id' => $newMappingVersion->id,
                        ], $latLng->toArrayWithFloor()));
                        $newMappingVersionHasDungeonStart = true;

                        $this->log->importMapPOIsCreatedNewDungeonStart($dungeonStart->id, $dungeonStart->floor_id);
                    } elseif ($mapIconTypeKey !== null) {
                        $mapIconTypeId = MapIconType::ALL[$mapIconTypeKey];

                        // Generic item icons were just unconditionally cleared from $newMappingVersion above
                        // (deleteClonedGenericItemMapIcons) so they can be re-created fresh from MDT below -
                        // checking $currentMappingVersion here would find the old version's now-deleted-in-
                        // $newMappingVersion copy and skip re-creating it, silently dropping the icon (#4112).
                        // No predecessor to match against - always create a new icon (#3757).
                        $existingMapIcon = in_array($mapIconTypeId, $genericItemMapIconTypeIds, true)
                            ? $newMappingVersion->getMapIconNearLocation($latLng, $mapIconTypeId)
                            : $currentMappingVersion?->getMapIconNearLocation($latLng, $mapIconTypeId);
                        if ($existingMapIcon === null) {
                            $translationKey = null;
                            if ($mdtMapPOI->getType() === MDTMapPOIType::GenericAssignablePOI &&
                                isset($mdtMapPOI->getInfo()['name'])) {
                                $translationKey = sprintf('mapping.map_icons.mdt.%s', Str::snake($mdtMapPOI->getInfo()['name']));

                                if (__($translationKey) === $translationKey) {
                                    // No translation found, fallback to the raw name
                                    $this->log->importMapPOIsMissingTranslation($translationKey);
                                }
                            }

                            $mapIcon = MapIcon::create(array_merge(array_filter([
                                'mapping_version_id' => $newMappingVersion->id,
                                'map_icon_type_id'   => $mapIconTypeId,
                                'comment'            => $translationKey,
                            ]), $latLng->toArrayWithFloor()));

                            $this->log->importMapPOIsCreatedNewMapIcon(
                                $mapIcon->id,
                                $mapIcon->floor_id,
                                $mapIcon->map_icon_type_id,
                            );
                        } else {
                            $this->log->importMapPOIsMapIconAlreadyExists(
                                $existingMapIcon->id,
                                $latLng->toArray(),
                                $mdtMapPOI->getType()->value,
                            );
                        }
                    } elseif ($mdtMapPOI->getType() === MDTMapPOIType::MapLink) {
                        // So because of the linked_floor_switch_id we cannot re-import dungeon floor switches
                        // We cannot for sure map the floor switches between different versions to one another
                        // We could use coordinates but if they change it's iffy.
                        // They also don't change between mapping versions, it's not really something Blizzard _can_ change
                        // Only create fresh markers when $newMappingVersion doesn't already have any - whether
                        // because copyMappingVersionContentsToDungeon() cloned them from $currentMappingVersion,
                        // or because a first-ever import for this game version cloned them from a prior game
                        // version's facade-source mapping version (#3757/#3762) - to avoid creating duplicates
                        // on top of either.
                        if (!$newMappingVersionHasDungeonFloorSwitchMarkers) {
                            $floor = $this->findFloorByMdtSubLevel($dungeon, $newMappingVersion, $mdtMapPOI->getSubLevel());

                            $latLng = Conversion::convertMDTCoordinateToLatLng([
                                'x' => $mdtMapPOI->getX(),
                                'y' => $mdtMapPOI->getY(),
                            ], $floor);
                            $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);

                            $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::create(array_merge([
                                'mapping_version_id' => $newMappingVersion->id,
                                'floor_id'           => $floor->id,
                                'target_floor_id'    => $this->findFloorByMdtSubLevel($dungeon, $newMappingVersion, $mdtMapPOI->getTarget())->id,
                            ], $latLng->toArray()));
                            $this->log->importMapPOIsNewDungeonFloorSwitchMarkerOK(
                                $dungeonFloorSwitchMarker->id,
                                $dungeonFloorSwitchMarker->floor_id,
                                $dungeonFloorSwitchMarker->target_floor_id,
                            );
                        } else {
                            $this->log->importMapPOIsHaveExistingFloorSwitchMarkers(
                                $newMappingVersion->dungeonFloorSwitchMarkers()->count(),
                            );
                        }
                    } elseif (Conversion::isMDTMapPOIUnhandled($mdtMapPOI)) {
                        // MDT draws something we have no map icon type for - our map will be missing it.
                        $this->log->importMapPOIsUnhandledMapPOI(
                            $mdtMapPOI->getType()->value,
                            $mdtMapPOI->getSpellId(),
                            $mdtMapPOI->getTextureFileDataId(),
                            $mdtMapPOI->getSubLevel(),
                        );
                    }
                }
            }
        } finally {
            $this->log->importMapPOIsEnd();
        }
    }

    /**
     * Refuses an import whose incoming NPCs have nothing in common with the dungeon's current mapping.
     *
     * MDT 6.2.1 shipped a `TheBlindingVale.lua` that was a byte-for-byte duplicate of Den of Nalorakk's, and
     * the import replaced The Blinding Vale's entire mapping with Den of Nalorakk's enemies at exit code 0,
     * without a single warning - nobody noticed until the map was eyeballed weeks later (#3995).
     *
     * The threshold is measured rather than guessed. Across all 307 consecutive mapping-version transitions
     * in the database, the lowest overlap of a legitimate transition was 59% and not one was 0%, so anything
     * under {@see self::NPC_SET_OVERLAP_MINIMUM} means the incoming data is wrong, not the mapping.
     *
     * @throws MDTMappingNpcSetReplacedException
     * @throws Exception
     */
    private function assertMDTNpcSetIsPlausible(
        Dungeon         $dungeon,
        ?MappingVersion $currentMappingVersion,
        bool            $forceImport,
    ): void {
        // Nothing to compare against on a first-ever import, or one whose predecessor holds no enemies
        if ($currentMappingVersion === null) {
            return;
        }

        $previousNpcIds = $currentMappingVersion->enemies()
            ->reorder()
            ->whereNotNull('npc_id')
            ->distinct()
            ->pluck('npc_id')
            ->all();

        if ($previousNpcIds === []) {
            return;
        }

        $incomingNpcIds = new MDTDungeon($this->cacheService, $this->coordinatesService, $dungeon)
            ->getMDTNPCs()
            ->map(static fn(MDTNpc $mdtNpc): int => $mdtNpc->getId())
            ->unique()
            ->all();

        if ($incomingNpcIds === []) {
            return;
        }

        $keptNpcCount   = count(array_intersect($previousNpcIds, $incomingNpcIds));
        $keptPercentage = (int)round(100 * $keptNpcCount / count($previousNpcIds));

        if ($keptNpcCount / count($previousNpcIds) >= self::NPC_SET_OVERLAP_MINIMUM) {
            return;
        }

        $this->log->importMappingVersionFromMDTNpcSetReplaced(
            $dungeon->key,
            count($previousNpcIds),
            count($incomingNpcIds),
            $keptPercentage,
            $forceImport,
        );

        if (!$forceImport) {
            throw new MDTMappingNpcSetReplacedException(
                $dungeon->key,
                count($previousNpcIds),
                count($incomingNpcIds),
                $keptPercentage,
                $this->findDungeonKeyOwningNpcs($dungeon, $incomingNpcIds),
            );
        }
    }

    /**
     * MDT owns the item icons it draws - every map icon type in
     * {@see Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING} exists for one specific MDT
     * `genericItem` POI and nothing else, so the import below re-creates the complete set from MDT.
     *
     * Until this import existed those items could only be placed by hand, and
     * `copyMappingVersionContentsToDungeon()` clones such an icon into every subsequent mapping version -
     * where it would now sit next to the imported copy as a duplicate. `getMapIconNearLocation()` does not
     * catch it: its window is +/-5 lat/lng, while Maisara Caverns' hand-placed Hearty Vilebranch Stew is
     * 5.3 off MDT's position and Seat of the Triumvirate's Void Infusion 13.9 (#3993).
     *
     * @param array<int> $genericItemMapIconTypeIds
     */
    private function deleteClonedGenericItemMapIcons(MappingVersion $newMappingVersion, array $genericItemMapIconTypeIds): void
    {
        $deletedCount = $newMappingVersion->mapIcons()
            ->whereIn('map_icon_type_id', $genericItemMapIconTypeIds)
            ->delete();

        if ($deletedCount > 0) {
            $this->log->importMapPOIsDeletedClonedGenericItemMapIcons($deletedCount);
        }
    }

    /**
     * @return array<int>
     */
    private function getGenericItemMapIconTypeIds(): array
    {
        return array_map(
            static fn(string $mapIconTypeKey): int => MapIconType::ALL[$mapIconTypeKey],
            array_values(Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING),
        );
    }

    /**
     * The dungeon that most of $npcIds are linked to, if it is not $dungeon itself - i.e. whose data MDT
     * appears to have handed us. Null when the NPCs are not predominantly another dungeon's.
     *
     * @param array<int> $npcIds
     */
    private function findDungeonKeyOwningNpcs(Dungeon $dungeon, array $npcIds): ?string
    {
        /** @var NpcDungeon|null $topNpcDungeon */
        $topNpcDungeon = NpcDungeon::query()
            ->selectRaw('dungeon_id, COUNT(*) AS npc_count')
            ->whereIn('npc_id', $npcIds)
            ->whereNot('dungeon_id', $dungeon->id)
            ->groupBy('dungeon_id')
            ->orderByDesc('npc_count')
            ->first();

        if ($topNpcDungeon === null || $topNpcDungeon->npc_count < count($npcIds) / 2) {
            return null;
        }

        return Dungeon::find($topNpcDungeon->dungeon_id)?->key;
    }

    public function findFloorByMdtSubLevel(Dungeon $dungeon, MappingVersion $mappingVersion, int $mdtSubLevel): Floor
    {
        $activeFloors = $dungeon->floors()->active()->get();

        // A facade floor is only a usable host for a coordinate when the mapping version actually has its
        // facade enabled - convertFacadeMapLocationToMapLocation() is a no-op otherwise, which would leave
        // the coordinate stranded on the facade floor and blow up in CoordinatesService. Facade floors are
        // seeded with mdt_sub_level = 1, so without this they win the lookup for sub level 1 on every
        // dungeon that has a facade floor but has not enabled it - Throne of the Tides today (#3742),
        // and every historical mapping version from before its dungeon's facade was switched on.
        if (!$mappingVersion->facade_enabled) {
            $activeFloors = $activeFloors->filter(static fn(Floor $floor) => !$floor->facade);
        }

        // First check for mdt_sub_level, if that isn't found just match on our own index
        return $activeFloors->first(static fn(
            Floor $floor,
        ) => $floor->mdt_sub_level === $mdtSubLevel) ?? $activeFloors->first(static fn(
            Floor $floor,
        ) => $floor->index === $mdtSubLevel);
    }

    /**
     * Recursively sorts the keys of any associative (non-list) array so that {@see getMDTMappingHash()} does not
     * depend on the Lua VM's unstable hash-part iteration order for nested `info` sub-tables (#4110).
     *
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function canonicalizeForHash(array $data): array
    {
        if (!array_is_list($data)) {
            ksort($data);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::canonicalizeForHash($value);
            }
        }

        return $data;
    }
}
