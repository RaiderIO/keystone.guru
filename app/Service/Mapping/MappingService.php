<?php

namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\EnemyForcesCheckpoint;
use App\Models\Floor\FloorUnion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\MDT\MDTAddonVersionServiceInterface;
use Illuminate\Support\Carbon;

class MappingService implements MappingServiceInterface
{
    public function __construct(private readonly MDTAddonVersionServiceInterface $mdtAddonVersionService)
    {
    }

    public function createNewBareMappingVersion(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion
    {
        /** @var MappingVersion|null $currentMappingVersion */
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        $newVersion            = (($currentMappingVersion?->version) ?? 0) + 1; // @phpstan-ignore nullsafe.neverNull

        $now = Carbon::now()->toDateTimeString();

        return MappingVersion::create([
            'dungeon_id'        => $dungeon->id,
            'game_version_id'   => $gameVersion->id,
            'mdt_mapping_hash'  => null,
            'mdt_addon_version' => null,
            // Explicitly set (matches the column default, true) rather than omitted - Eloquent's create()
            // does not hydrate the in-memory model with a DB-applied default, so callers reading the
            // returned instance's mdt_changes_pending right away would otherwise see null until a refresh.
            // Not imported from MDT, so it must never be the target of an MDT string import (#4280).
            'mdt_changes_pending' => true,
            'version'             => $newVersion,
            'facade_enabled'      => false,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
    }

    public function createNewMappingVersionFromPreviousMapping(
        Dungeon     $dungeon,
        GameVersion $gameVersion,
    ): MappingVersion {
        /** @var MappingVersion|null $currentMappingVersion */
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        $newVersion            = (($currentMappingVersion?->version) ?? 0) + 1; // @phpstan-ignore nullsafe.neverNull

        $now = Carbon::now()->toDateTimeString();

        return MappingVersion::create([
            'dungeon_id'        => $dungeon->id,
            'game_version_id'   => $gameVersion->id,
            'mdt_mapping_hash'  => $currentMappingVersion?->mdt_mapping_hash ?? null, // @phpstan-ignore nullsafe.neverNull
            'mdt_addon_version' => $currentMappingVersion?->mdt_addon_version ?? null, // @phpstan-ignore nullsafe.neverNull
            // The hash/addon version above are inherited to keep the MDT era this mapping descends from,
            // but the mapping itself is now ours to edit and no longer matches MDT's (#4280). Set explicitly
            // (matches the column default, true) since ::create() doesn't hydrate the in-memory model with
            // a DB-applied default, and callers read this instance right away.
            'mdt_changes_pending' => true,
            'version'             => $newVersion,
            'facade_enabled'      => $currentMappingVersion?->facade_enabled ?? false, // @phpstan-ignore nullsafe.neverNull
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
    }

    public function createNewMappingVersionFromMDTMapping(
        Dungeon         $dungeon,
        GameVersion     $gameVersion,
        ?MappingVersion $currentMappingVersion,
    ): MappingVersion {
        // facade_enabled is a PHYSICAL fact about the dungeon (does it have a facade floor at all), not
        // game-version-specific curated content, and it does not need to be inherited from any mapping
        // version - Floor::facade is the authoritative source (#3757). Resolving it from a mapping version
        // instead risks picking up one whose facade_enabled happens to be false for that particular game
        // version (seen in production: e.g. dungeon 12's legion-remix/dungeon 49's wotlk mapping versions),
        // even though the dungeon itself is a facade dungeon.
        $facadeEnabled = $dungeon->getFacadeFloor() !== null;

        // FloorUnions (and areas) ARE actual rows, not a boolean, so they still need a concrete source
        // mapping version to clone from for a genuinely first-ever import of a game version. Pin that source
        // explicitly and deterministically to the newest facade-enabled mapping version of ANY game version -
        // never resolve it ambiently via Dungeon::getCurrentMappingVersion(), which falls back through
        // GameVersion::getUserOrDefaultGameVersion()/authenticated-user context that doesn't exist for this
        // pipeline's real caller (the mdt:importmapping CLI command), and can silently source the clone from
        // the wrong game version's (facade-disabled) mapping version (#3757).
        $facadeSourceMappingVersion = $currentMappingVersion
            ?? ($facadeEnabled
                ? $dungeon->mappingVersions()->where('facade_enabled', true)->orderByDesc('id')->first()
                : null);

        $now = Carbon::now()->toDateTimeString();
        // This needs to happen quietly as to not trigger MappingVersion events defined in its class
        $id = MappingVersion::insertGetId([
            'dungeon_id' => $dungeon->id,
            // $gameVersion is the source of truth, NOT $currentMappingVersion->game_version_id - the caller is
            // responsible for scoping $currentMappingVersion to $gameVersion, and it may legitimately be null
            // (the dungeon's first-ever mapping version for this game version) (#3757).
            'game_version_id' => $gameVersion->id,
            // Set only once the import actually succeeds, see importMappingVersionFromMDT() (#3737)
            'mdt_mapping_hash'  => null,
            'mdt_addon_version' => $this->mdtAddonVersionService->getCurrentAddonVersion(),
            // Imported straight from MDT, so MDT strings may resolve onto it (#4280)
            'mdt_changes_pending' => false,
            'version'             => ($currentMappingVersion?->version ?? 0) + 1, // @phpstan-ignore nullsafe.neverNull
            'facade_enabled'      => $facadeEnabled,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        $newMappingVersion = MappingVersion::find($id);

        if ($currentMappingVersion === null) {
            // Checkpoints/enemies stay empty for a genuinely first-ever import of this game version - there is
            // no game-version-agnostic source for curated content like that. Map icons ALSO stay empty here,
            // but not because they're curated: importMapPOIs() (called right after this method returns, as
            // part of the same import) already (re)creates the physical subset it recognizes itself from MDT's
            // own map POI data whenever $currentMappingVersion is null - see its "(#3757)" comments. Cloning
            // them here too would just create duplicate rows a few lines later in the same import. Mountable
            // areas have no such backfill to rely on: MDT has no mount-speed-zone concept at all, so they need
            // the same explicit clone floor unions get.
            // The physical facade geometry resolved above still needs cloning in, though, along with
            // timer_max_seconds - it's a NOT NULL DEFAULT 0 column that several call sites divide by unguarded
            // (CombatLogEventFilter::fromHeatmapDataFilter(), CombatLogEvent::setTimeInterval()), so leaving it
            // at its column default here is a live DivisionByZeroError, not just missing data.
            if ($facadeSourceMappingVersion !== null) {
                $this->cloneFloorUnionsToMappingVersion($facadeSourceMappingVersion, $newMappingVersion);
                $this->cloneMountableAreasToMappingVersion($facadeSourceMappingVersion, $newMappingVersion);

                $newMappingVersion->update([
                    'timer_max_seconds' => $facadeSourceMappingVersion->timer_max_seconds,
                ]);
            }

            // Dungeon floor switch markers have no MDT backfill either, but unlike FloorUnions they are NOT
            // facade-specific geometry - every dungeon has staircases between floors, facade-enabled or not
            // (per Wotuu, #3762 review round 2: modern MDT exports only generate a combined/facade view and no
            // longer supply the per-floor MapLink POI data importMapPOIs() used to recreate them from, and this
            // app still needs them for both facade mode and the per-floor "visit style" mode). So their source
            // must NOT be gated behind $facadeSourceMappingVersion the way FloorUnion/MountableArea legitimately
            // are - that would silently drop them for every non-facade dungeon's first-ever import, which is
            // most of them. Fall back to the newest mapping version of ANY game version, deterministically,
            // same rationale as the facade source above.
            $physicalGeometrySourceMappingVersion = $facadeSourceMappingVersion
                ?? $dungeon->mappingVersions()->orderByDesc('id')->first();

            if ($physicalGeometrySourceMappingVersion !== null) {
                $this->cloneDungeonFloorSwitchMarkersToMappingVersion($physicalGeometrySourceMappingVersion, $newMappingVersion);
            }

            // importMapPOIs() still guards against duplicating whatever gets cloned in here (it only creates a
            // fresh floor switch marker when the new mapping version doesn't already have one).

            return $newMappingVersion->load([
                'dungeonFloorSwitchMarkers',
                'mapIcons',
                'mountableAreas',
                'floorUnions',
                'floorUnionAreas',
            ]);
        }

        return $this->copyMappingVersionContentsToDungeon($currentMappingVersion, $newMappingVersion);
    }

    /**
     * Resolves the mapping version that best matches an imported MDT string's `addonVersion`, so a
     * route imported from an older MDT string is attached to the mapping version of that MDT era
     * (and thus flagged as outdated, offering an upgrade). See #3380.
     *
     * The `addonVersion` integer is not orderable across MDT's historical version schemes, so it is
     * resolved to its upstream release date and all comparisons happen on dates. A mapping version
     * imported from MDT version X covers the half-open range (previous import, X]: the chosen version
     * is the OLDEST one whose imported-from release date is at or after the string's release date. When
     * multiple candidates share the same imported-from date (e.g. a manual/facade clone that inherited
     * its parent's `mdt_addon_version`), the highest `version` among them wins.
     *
     * Mapping versions flagged `mdt_changes_pending` - ones we created ourselves, whose mapping diverges
     * from what MDT ships until MDT accepts the change - are never a valid target: the enemies an MDT
     * string references are MDT's, so importing them into our own diverged mapping mismatches them
     * (#4280). They are excluded from the candidates AND from every fallback below; the ceiling for an
     * import is therefore the newest mapping version MDT still matches.
     *
     * Falls back to that newest MDT-matching mapping version when the string carries no `addonVersion`
     * (Keystone's own exports, very old strings), when the version is unknown, or when the string is
     * newer than anything imported (the user is genuinely ahead of the server). A dungeon whose every
     * mapping version is flagged falls back to the current one, so this never returns null for a dungeon
     * that has a mapping version at all - callers dereference the result directly.
     */
    public function getMappingVersionForMdtAddonVersion(Dungeon $dungeon, ?int $addonVersion, ?GameVersion $gameVersion = null): ?MappingVersion
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);

        if ($currentMappingVersion === null) {
            return null;
        }

        /** @var MappingVersion|null $newestMdtMappingVersion */
        $newestMdtMappingVersion = $dungeon->mappingVersions()
            ->where('game_version_id', $currentMappingVersion->game_version_id)
            ->where('mdt_changes_pending', false)
            ->reorder('mapping_versions.version', 'desc')
            ->without('dungeon')
            ->first();

        $fallbackMappingVersion = $newestMdtMappingVersion ?? $currentMappingVersion;

        if ($addonVersion === null || $addonVersion === 0) {
            return $fallbackMappingVersion;
        }

        $stringDate = $this->mdtAddonVersionService->getReleaseDate($addonVersion);

        // Unknown addonVersion (newer than what has been synced, or a value with no release) → newest.
        if ($stringDate === null) {
            return $fallbackMappingVersion;
        }

        /** @var \Illuminate\Support\Collection<int, MappingVersion> $candidates */
        $candidates = $dungeon->mappingVersions()
            ->where('game_version_id', $currentMappingVersion->game_version_id)
            ->where('mdt_changes_pending', false)
            ->reorder('mapping_versions.version', 'asc')
            ->without('dungeon')
            ->get();

        $match     = null;
        $matchDate = null;
        foreach ($candidates as $candidate) {
            $candidateDate = ($candidate->mdt_addon_version !== null
                ? $this->mdtAddonVersionService->getReleaseDate($candidate->mdt_addon_version)
                : null) ?? $candidate->created_at;

            if ($candidateDate->lessThan($stringDate)) {
                continue;
            }

            if ($match === null) {
                $match     = $candidate;
                $matchDate = $candidateDate;
            } elseif ($candidateDate->equalTo($matchDate)) {
                // Same imported-from date, higher version (candidates are ordered version asc) wins.
                $match = $candidate;
            } else {
                // The imported-from date has moved past the matched window; stop.
                break;
            }
        }

        // String is newer than every mapping version we imported → newest MDT-matching one is correct.
        if ($match === null) {
            return $fallbackMappingVersion;
        }

        // Re-fetch as a single model (mirroring getCurrentMappingVersion) so downstream lazy-loads such as
        // ->enemies are permitted; models pulled from the candidate collection above would trip the guard.
        return $dungeon->mappingVersions()->without('dungeon')->find($match->id) ?? $fallbackMappingVersion;
    }

    public function copyMappingVersionToDungeon(MappingVersion $sourceMappingVersion, Dungeon $dungeon): MappingVersion
    {
        // The target's game_version_id and next `version` must be derived from $sourceMappingVersion's
        // OWN game version, not $dungeon's ambient "current" mapping version - that resolves through
        // the acting user's/default game version and can land on a completely different
        // game_version_id than the one actually being copied (see #3720).
        /** @var MappingVersion|null $currentMappingVersionForGameVersion */
        $currentMappingVersionForGameVersion = $dungeon->getCurrentMappingVersionForGameVersion($sourceMappingVersion->gameVersion);
        $now                                 = Carbon::now()->toDateTimeString();
        // This needs to happen quietly as to not trigger MappingVersion events defined in its class
        $id = MappingVersion::insertGetId([
            'dungeon_id'        => $dungeon->id,
            'game_version_id'   => $sourceMappingVersion->game_version_id,
            'mdt_mapping_hash'  => $sourceMappingVersion->mdt_mapping_hash,
            'mdt_addon_version' => $sourceMappingVersion->mdt_addon_version,
            // A copy onto another dungeon is ours, not MDT's - see createNewMappingVersionFromPreviousMapping
            // (#4280). mdt_changes_pending intentionally omitted - the column default (true) covers this.
            'version'        => ($currentMappingVersionForGameVersion?->version ?? 0) + 1, // @phpstan-ignore nullsafe.neverNull
            'facade_enabled' => $currentMappingVersionForGameVersion?->facade_enabled ?? false, // @phpstan-ignore nullsafe.neverNull
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        return MappingVersion::find($id);
    }

    public function copyMappingVersionContentsToDungeon(
        MappingVersion $sourceMappingVersion,
        MappingVersion $targetMappingVersion,
    ): MappingVersion {
        // Copy all elements over from the previous mapping version - this allows us to keep adding elements regardless of
        // MDT mapping

        // Dungeon Floor Switch Markers
        $this->cloneDungeonFloorSwitchMarkersToMappingVersion($sourceMappingVersion, $targetMappingVersion);

        // Map Icons
        foreach ($sourceMappingVersion->mapIcons as $mapIcon) {
            $mapIcon->cloneForNewMappingVersion($targetMappingVersion);
        }

        // Mountable Areas
        $this->cloneMountableAreasToMappingVersion($sourceMappingVersion, $targetMappingVersion);

        // Enemy Forces Checkpoints are cloned by copyEnemyForcesCheckpointsToMappingVersion() instead - the
        // caller must invoke it, because only the caller knows how to re-link the membership of the enemies
        // it clones or re-imports, and this method copies no enemies at all (#3702).

        // Floor Unions (and areas)
        $this->cloneFloorUnionsToMappingVersion($sourceMappingVersion, $targetMappingVersion);

        // Copy these properties over only if the dungeons match - doesn't make sense otherwise
        if ($sourceMappingVersion->dungeon_id === $targetMappingVersion->dungeon_id) {
            $targetMappingVersion->update([
                'enemy_forces_required'           => $sourceMappingVersion->enemy_forces_required,
                'enemy_forces_required_teeming'   => $sourceMappingVersion->enemy_forces_required_teeming,
                'enemy_forces_shrouded'           => $sourceMappingVersion->enemy_forces_shrouded,
                'enemy_forces_shrouded_zul_gamux' => $sourceMappingVersion->enemy_forces_shrouded_zul_gamux,
                'timer_max_seconds'               => $sourceMappingVersion->timer_max_seconds,
                'facade_enabled'                  => $sourceMappingVersion->facade_enabled,
            ]);
        }

        // Load the newly generated relationships
        $targetMappingVersion->load([
            'dungeonFloorSwitchMarkers',
            'mapIcons',
            'mountableAreas',
            'floorUnions',
            'floorUnionAreas',
        ]);

        return $targetMappingVersion;
    }

    public function copyEnemyForcesCheckpointsToMappingVersion(
        ?MappingVersion $sourceMappingVersion,
        MappingVersion  $targetMappingVersion,
    ): array {
        $enemyForcesCheckpointIdMapping = [];

        // No predecessor to clone from (e.g. a dungeon's first-ever mapping version for a game version) - the
        // target simply starts with none, same as a bare mapping version (#3757).
        if ($sourceMappingVersion !== null) {
            foreach ($sourceMappingVersion->enemyForcesCheckpoints as $enemyForcesCheckpoint) {
                /** @var EnemyForcesCheckpoint $newEnemyForcesCheckpoint */
                $newEnemyForcesCheckpoint = $enemyForcesCheckpoint->cloneForNewMappingVersion($targetMappingVersion);

                $enemyForcesCheckpointIdMapping[$enemyForcesCheckpoint->id] = $newEnemyForcesCheckpoint->id;
            }
        }

        $targetMappingVersion->load('enemyForcesCheckpoints');

        return $enemyForcesCheckpointIdMapping;
    }

    private function cloneFloorUnionsToMappingVersion(MappingVersion $sourceMappingVersion, MappingVersion $targetMappingVersion): void
    {
        foreach ($sourceMappingVersion->floorUnions as $floorUnion) {
            /** @var FloorUnion $newFloorUnion */
            $newFloorUnion = $floorUnion->cloneForNewMappingVersion($targetMappingVersion);
            foreach ($floorUnion->floorUnionAreas as $floorUnionArea) {
                $floorUnionArea->cloneForNewMappingVersion($targetMappingVersion, $newFloorUnion);
            }
        }
    }

    private function cloneMountableAreasToMappingVersion(MappingVersion $sourceMappingVersion, MappingVersion $targetMappingVersion): void
    {
        foreach ($sourceMappingVersion->mountableAreas as $mountableArea) {
            $mountableArea->cloneForNewMappingVersion($targetMappingVersion);
        }
    }

    private function cloneDungeonFloorSwitchMarkersToMappingVersion(MappingVersion $sourceMappingVersion, MappingVersion $targetMappingVersion): void
    {
        $dungeonFloorSwitchMarkerIdMapping = [];
        $newDungeonFloorSwitchMarkers      = [];

        foreach ($sourceMappingVersion->dungeonFloorSwitchMarkers as $dungeonFloorSwitchMarker) {
            /** @var DungeonFloorSwitchMarker $newDungeonFloorSwitchMarker */
            $newDungeonFloorSwitchMarker = $dungeonFloorSwitchMarker->cloneForNewMappingVersion(
                $targetMappingVersion,
            );
            $dungeonFloorSwitchMarkerIdMapping[$dungeonFloorSwitchMarker->id] = $newDungeonFloorSwitchMarker->id;
            $newDungeonFloorSwitchMarkers[]                                   = $newDungeonFloorSwitchMarker;
        }

        // Restore the links between the floor switches
        foreach ($newDungeonFloorSwitchMarkers as $newDungeonFloorSwitchMarker) {
            $newDungeonFloorSwitchMarker->update([
                'linked_dungeon_floor_switch_marker_id' => $dungeonFloorSwitchMarkerIdMapping[$newDungeonFloorSwitchMarker['linked_dungeon_floor_switch_marker_id']] ?? null,
            ]);
        }
    }
}
