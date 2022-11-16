<?php


namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingCommitLog;
use App\Models\Mapping\MappingVersion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MappingService implements MappingServiceInterface
{
    /**
     * @return bool
     */
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
            ->keyBy(function (Dungeon $dungeon) {
                return $dungeon->id;
            });
    }

    /**
     * @inheritDoc
     */
    public function createNewMappingVersion(Dungeon $dungeon, ?string $hash, bool $quietly = false): MappingVersion
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        $attributes = [
            'dungeon_id'       => $dungeon->id,
            'mdt_mapping_hash' => $hash,
            'version'          => ++$currentMappingVersion->version,
            'created_at'       => Carbon::now()->toDateTimeString(),
            'updated_at'       => Carbon::now()->toDateTimeString(),
        ];

        if ($quietly) {
            $id     = MappingVersion::insertGetId($attributes);
            $result = MappingVersion::find($id);
        } else {
            $result = MappingVersion::create($attributes);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getMappingVersionOrNew(Dungeon $dungeon): MappingVersion
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        $wasRecentlyChanged = $this->getDungeonsWithUnmergedMappingChanges()->has($dungeon->id);

        // If we were recently changed, it means a new mapping version was already created (by the request that triggered
        // the creation of the mapping version). If we are the first mapping change for this dungeon since the last merge,
        // we create a new mapping version and return that.
        if ($wasRecentlyChanged) {
            $result = $currentMappingVersion;
        } else {
            $result = $this->createNewMappingVersion($dungeon);
        }

        return $result;
    }
}
