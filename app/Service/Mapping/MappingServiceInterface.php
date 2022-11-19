<?php


namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

interface MappingServiceInterface
{
    /**
     * @return bool True if the mapping has changed since last time we synchronized it, and we need to synchronize it again.
     */
    public function shouldSynchronizeMapping(): bool;

    /**
     * @return Collection A list of all changes to the mapping that have not been synchronized yet.
     */
    public function getUnmergedMappingChanges(): Collection;

    /**
     * @return Collection Gets a list of dungeons of which the mapping has changed since the last time a synchronization was done.
     */
    public function getDungeonsWithUnmergedMappingChanges(): Collection;

    /**
     * @param Dungeon $dungeon
     * @return MappingVersion
     */
    public function createNewMappingVersionFromPreviousMapping(Dungeon $dungeon): MappingVersion;

    /**
     * Creates a new mapping version for a dungeon.
     *
     * @param Dungeon $dungeon
     * @param string|null $hash
     * @return MappingVersion
     */
    public function createNewMappingVersionFromMDTMapping(Dungeon $dungeon, ?string $hash): MappingVersion;

    /**
     * Gets a mapping version of a dungeon, or creates a new one for this dungeon if the most recent version has been pushed.
     *
     * This is useful for when the mapping changes - it determines if we need to insert a new version or not.
     *
     * @param Dungeon $dungeon
     * @return void
     */
    public function getMappingVersionOrNew(Dungeon $dungeon): MappingVersion;
}
