<?php

namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
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
            'version'           => $newVersion,
            'facade_enabled'    => false,
            'created_at'        => $now,
            'updated_at'        => $now,
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
            'version'           => $newVersion,
            'facade_enabled'    => $currentMappingVersion?->facade_enabled ?? false, // @phpstan-ignore nullsafe.neverNull
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
    }

    public function createNewMappingVersionFromMDTMapping(Dungeon $dungeon, ?GameVersion $gameVersion, ?string $hash): MappingVersion
    {
        /** @var MappingVersion|null $currentMappingVersion */
        $currentMappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);
        $now                   = Carbon::now()->toDateTimeString();
        // This needs to happen quietly as to not trigger MappingVersion events defined in its class
        $id = MappingVersion::insertGetId([
            'dungeon_id'        => $dungeon->id,
            'game_version_id'   => $currentMappingVersion?->game_version_id ?? GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL], // @phpstan-ignore nullsafe.neverNull
            'mdt_mapping_hash'  => $hash,
            'mdt_addon_version' => $this->mdtAddonVersionService->getCurrentAddonVersion(),
            'version'           => ($currentMappingVersion?->version ?? 0) + 1, // @phpstan-ignore nullsafe.neverNull
            'facade_enabled'    => $currentMappingVersion?->facade_enabled ?? false, // @phpstan-ignore nullsafe.neverNull
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        $newMappingVersion = MappingVersion::find($id);

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
     * Falls back to the current mapping version when the string carries no `addonVersion` (Keystone's
     * own exports, very old strings), when the version is unknown, or when the string is newer than
     * anything imported (the user is genuinely ahead of the server).
     */
    public function getMappingVersionForMdtAddonVersion(Dungeon $dungeon, ?int $addonVersion, ?GameVersion $gameVersion = null): ?MappingVersion
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);

        if ($addonVersion === null || $addonVersion === 0 || $currentMappingVersion === null) {
            return $currentMappingVersion;
        }

        $stringDate = $this->mdtAddonVersionService->getReleaseDate($addonVersion);

        // Unknown addonVersion (newer than what has been synced, or a value with no release) → newest.
        if ($stringDate === null) {
            return $currentMappingVersion;
        }

        /** @var \Illuminate\Support\Collection<int, MappingVersion> $candidates */
        $candidates = $dungeon->mappingVersions()
            ->where('game_version_id', $currentMappingVersion->game_version_id)
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

        // String is newer than every mapping version we imported → current (newest) is correct.
        if ($match === null) {
            return $currentMappingVersion;
        }

        // Re-fetch as a single model (mirroring getCurrentMappingVersion) so downstream lazy-loads such as
        // ->enemies are permitted; models pulled from the candidate collection above would trip the guard.
        return $dungeon->mappingVersions()->without('dungeon')->find($match->id) ?? $currentMappingVersion;
    }

    public function copyMappingVersionToDungeon(MappingVersion $sourceMappingVersion, Dungeon $dungeon): MappingVersion
    {
        /** @var MappingVersion|null $currentMappingVersion */
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();
        $now                   = Carbon::now()->toDateTimeString();
        // This needs to happen quietly as to not trigger MappingVersion events defined in its class
        $id = MappingVersion::insertGetId([
            'dungeon_id'        => $dungeon->id,
            'game_version_id'   => $currentMappingVersion?->game_version_id ?? GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL], // @phpstan-ignore nullsafe.neverNull
            'mdt_mapping_hash'  => $sourceMappingVersion->mdt_mapping_hash,
            'mdt_addon_version' => $sourceMappingVersion->mdt_addon_version,
            'version'           => ($currentMappingVersion?->version ?? 0) + 1, // @phpstan-ignore nullsafe.neverNull
            'facade_enabled'    => $currentMappingVersion?->facade_enabled ?? false, // @phpstan-ignore nullsafe.neverNull
            'created_at'        => $now,
            'updated_at'        => $now,
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

        // Map Icons
        foreach ($sourceMappingVersion->mapIcons as $mapIcon) {
            $mapIcon->cloneForNewMappingVersion($targetMappingVersion);
        }

        // Mountable Areas
        foreach ($sourceMappingVersion->mountableAreas as $mountableArea) {
            $mountableArea->cloneForNewMappingVersion($targetMappingVersion);
        }

        // Floor Unions (and areas)
        foreach ($sourceMappingVersion->floorUnions as $floorUnion) {
            /** @var FloorUnion $newFloorUnion */
            $newFloorUnion = $floorUnion->cloneForNewMappingVersion($targetMappingVersion);
            foreach ($floorUnion->floorUnionAreas as $floorUnionArea) {
                $floorUnionArea->cloneForNewMappingVersion($targetMappingVersion, $newFloorUnion);
            }
        }

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
}
