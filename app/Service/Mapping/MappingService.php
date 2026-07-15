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
