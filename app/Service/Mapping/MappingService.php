<?php

namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingCommitLog;
use App\Models\Mapping\MappingVersion;
use Carbon\Carbon;
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
     * @return Collection|MappingChangeLog[]
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
     * @return Collection|Dungeon[]
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
            ->keyBy(fn (Dungeon $dungeon) => $dungeon->id);
    }

    /**
     * {@inheritDoc}
     */
    public function createNewMappingVersionFromPreviousMapping(Dungeon $dungeon): MappingVersion
    {
        $currentMappingVersion = $dungeon->currentMappingVersion;

        return MappingVersion::create([
            'dungeon_id' => $dungeon->id,
            'mdt_mapping_hash' => optional($currentMappingVersion)->mdt_mapping_hash ?? '',
            'version' => (++optional($currentMappingVersion)->version) ?? 1,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function createNewMappingVersionFromMDTMapping(Dungeon $dungeon, ?string $hash): MappingVersion
    {
        $currentMappingVersion = $dungeon->currentMappingVersion;

        $newMappingVersionVersion = (optional($currentMappingVersion)->version ?? 0) + 1;

        // This needs to happen quietly as to not trigger MappingVersion events defined in its class
        $id = MappingVersion::insertGetId([
            'dungeon_id' => $dungeon->id,
            'mdt_mapping_hash' => $hash,
            'version' => $newMappingVersionVersion,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);

        $newMappingVersion = MappingVersion::find($id);

        // Copy all elements over from the previous mapping version - this allows us to keep adding elements regardless of
        // MDT mapping
        if ($currentMappingVersion !== null) {
            foreach ($currentMappingVersion->mapIcons as $mapIcon) {
                $mapIcon->cloneForNewMappingVersion($newMappingVersion);
            }

            foreach ($currentMappingVersion->mountableAreas as $mountableArea) {
                $mountableArea->cloneForNewMappingVersion($newMappingVersion);
            }

            foreach ($currentMappingVersion->floorUnions as $floorUnion) {
                /** @var FloorUnion $newFloorUnion */
                $newFloorUnion = $floorUnion->cloneForNewMappingVersion($newMappingVersion);
                foreach ($floorUnion->floorUnionAreas as $floorUnionArea) {
                    $floorUnionArea->cloneForNewMappingVersion($newMappingVersion, $newFloorUnion);
                }
            }

            // Load the newly generated relationships
            $newMappingVersion->load(['mapIcons', 'mountableAreas', 'floorUnions', 'floorUnionAreas']);
        }

        return $newMappingVersion;
    }

    /**
     * {@inheritDoc}
     */
    public function getMappingVersionOrNew(Dungeon $dungeon): MappingVersion
    {
        $currentMappingVersion = $dungeon->currentMappingVersion;

        $wasRecentlyChanged = $this->getDungeonsWithUnmergedMappingChanges()->has($dungeon->id);

        // If we were recently changed, it means a new mapping version was already created (by the request that triggered
        // the creation of the mapping version). If we are the first mapping change for this dungeon since the last merge,
        // we create a new mapping version and return that.
        if ($wasRecentlyChanged) {
            $result = $currentMappingVersion;
        } else {
            $result = $this->createNewMappingVersionFromPreviousMapping($dungeon);
        }

        return $result;
    }
}
