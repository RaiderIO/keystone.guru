<?php

namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Floor\FloorUnion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingCommitLog;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MappingService implements MappingServiceInterface
{
    public function shouldSynchronizeMapping(): bool
    {
        /** @var MappingChangeLog $mostRecentMappingChangeLog */
        $mostRecentMappingChangeLog = MappingChangeLog::latest()->first();

        /** @var MappingCommitLog $mostRecentMappingCommitLog */
        $mostRecentMappingCommitLog = MappingCommitLog::latest()->first();

        // If not synced at all yet, or if we've synced, but it was before any changes were done
        return $mostRecentMappingChangeLog !== null && ($mostRecentMappingCommitLog === null || $mostRecentMappingChangeLog->shouldSynchronize($mostRecentMappingCommitLog));
    }

    /**
     * @return Collection<MappingChangeLog>
     */
    public function getUnmergedMappingChanges(): Collection
    {
        $mostRecentlyMergedMappingCommitLog = MappingCommitLog::where('merged', 1)->orderBy('id', 'desc')->first();

        if ($mostRecentlyMergedMappingCommitLog !== null) {
            // Get all changes that have been done right after the most recently merged commit
            $result = MappingChangeLog::where('created_at', '>', $mostRecentlyMergedMappingCommitLog->created_at->toDateTimeString())->get();
        } else {
            $result = MappingChangeLog::all();
        }

        return $result;
    }

    /**
     * @return Collection<Dungeon>
     */
    public function getDungeonsWithUnmergedMappingChanges(): Collection
    {
        $mostRecentlyMergedMappingCommitLog = MappingCommitLog::where('merged', 1)->orderBy('id', 'desc')->first();

        if ($mostRecentlyMergedMappingCommitLog !== null) {
            $dungeonQueryBuilder = Dungeon::select('dungeons.*')
                ->join('mapping_change_logs', 'dungeons.id', 'mapping_change_logs.dungeon_id')
                ->where('mapping_change_logs.created_at', '>', $mostRecentlyMergedMappingCommitLog->created_at->toDateTimeString())
                ->groupBy('dungeon_id');
        } else {
            // Get all of them instead
            $dungeonQueryBuilder = Dungeon::select('dungeons.*')
                ->join('mapping_change_logs', 'dungeons.id', 'mapping_change_logs.dungeon_id');
        }

        return $dungeonQueryBuilder
            ->whereNotNull('dungeon_id')
            ->get()
            ->keyBy(static fn(Dungeon $dungeon) => $dungeon->id);
    }

    public function createNewBareMappingVersion(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        $newVersion            = (($currentMappingVersion?->version) ?? 0) + 1;

        $now = Carbon::now()->toDateTimeString();

        return MappingVersion::create([
            'dungeon_id'       => $dungeon->id,
            'game_version_id'  => $gameVersion->id,
            'mdt_mapping_hash' => null,
            'version'          => $newVersion,
            'facade_enabled'   => false,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    public function createNewMappingVersionFromPreviousMapping(
        Dungeon     $dungeon,
        GameVersion $gameVersion,
    ): MappingVersion {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        $newVersion            = (($currentMappingVersion?->version) ?? 0) + 1;

        $now = Carbon::now()->toDateTimeString();

        return MappingVersion::create([
            'dungeon_id'       => $dungeon->id,
            'game_version_id'  => $gameVersion->id,
            'mdt_mapping_hash' => $currentMappingVersion?->mdt_mapping_hash ?? null,
            'version'          => $newVersion,
            'facade_enabled'   => $currentMappingVersion?->facade_enabled ?? false,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    public function createNewMappingVersionFromMDTMapping(Dungeon $dungeon, ?GameVersion $gameVersion, ?string $hash): MappingVersion
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);
        $now                   = Carbon::now()->toDateTimeString();
        // This needs to happen quietly as to not trigger MappingVersion events defined in its class
        $id = MappingVersion::insertGetId([
            'dungeon_id'       => $dungeon->id,
            'game_version_id'  => $currentMappingVersion?->game_version_id ?? GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'mdt_mapping_hash' => $hash,
            'version'          => ($currentMappingVersion?->version ?? 0) + 1,
            'facade_enabled'   => $currentMappingVersion?->facade_enabled ?? false,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $newMappingVersion = MappingVersion::find($id);

        return $this->copyMappingVersionContentsToDungeon($currentMappingVersion, $newMappingVersion);
    }

    public function copyMappingVersionToDungeon(MappingVersion $sourceMappingVersion, Dungeon $dungeon): MappingVersion
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();
        $now                   = Carbon::now()->toDateTimeString();
        // This needs to happen quietly as to not trigger MappingVersion events defined in its class
        $id = MappingVersion::insertGetId([
            'dungeon_id'       => $dungeon->id,
            'game_version_id'  => $currentMappingVersion?->game_version_id ?? GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'mdt_mapping_hash' => $sourceMappingVersion->mdt_mapping_hash,
            'version'          => ($currentMappingVersion?->version ?? 0) + 1,
            'facade_enabled'   => $currentMappingVersion?->facade_enabled ?? false,
            'created_at'       => $now,
            'updated_at'       => $now,
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

    /**
     * {@inheritDoc}
     */
    public function getMappingVersionOrNew(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        $wasRecentlyChanged = $this->getDungeonsWithUnmergedMappingChanges()->has($dungeon->id);

        // If we were recently changed, it means a new mapping version was already created (by the request that triggered
        // the creation of the mapping version). If we are the first mapping change for this dungeon since the last merge,
        // we create a new mapping version and return that.
        if ($wasRecentlyChanged) {
            $result = $currentMappingVersion;
        } else {
            $result = $this->createNewMappingVersionFromPreviousMapping($dungeon, $gameVersion);
        }

        return $result;
    }
}
