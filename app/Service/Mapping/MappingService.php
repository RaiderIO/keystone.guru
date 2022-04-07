<?php


namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingCommitLog;
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
    public function getRecentlyChangedDungeons(): Collection
    {
        /** @var Collection|Dungeon[] $result */
        $result = collect();

        $mostRecentMappingChanges = $this->getUnmergedMappingChanges();

        foreach ($mostRecentMappingChanges as $mappingChange) {
            // Decode the latest known value
            $decoded = json_decode(!empty($mappingChange->after_model) ? $mappingChange->after_model : $mappingChange->before_model, true);

            // Only if we actually decoded something; prevents crashes
            if ($decoded !== false) {
                $dungeon = null;
                if (isset($decoded['dungeon_id']) && (int)$decoded['dungeon_id'] > 0) {
                    $dungeon = Dungeon::find($decoded['dungeon_id']);
                } else if (isset($decoded['floor_id']) && (int)$decoded['floor_id'] > 0) {
                    $dungeon = Floor::find($decoded['floor_id'])->dungeon;
                }

                // If we found the floor that was changed, add its dungeon to the list if it wasn't already in there
                if ($dungeon !== null) {
                    $exists = false;

                    foreach ($result as $changedDungeon) {
                        if ($changedDungeon->id === $dungeon->id) {
                            $exists = true;
                            break;
                        }
                    }


                    if (!$exists) {
                        $result->add($dungeon);
                    }
                }
            }
        }

        return $result;
    }


}
