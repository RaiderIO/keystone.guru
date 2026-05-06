<?php

namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
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
     * @return Collection<Dungeon> Gets a list of dungeons of which the mapping has changed since the last time a synchronization was done.
     */
    public function getDungeonsWithUnmergedMappingChanges(): Collection;

    public function createNewBareMappingVersion(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion;

    public function createNewMappingVersionFromPreviousMapping(
        Dungeon     $dungeon,
        GameVersion $gameVersion,
    ): MappingVersion;

    /**
     * Creates a new mapping version for a dungeon.
     */
    public function createNewMappingVersionFromMDTMapping(Dungeon $dungeon, ?GameVersion $gameVersion, ?string $hash): MappingVersion;

    /**
     * Takes an existing mapping version and applies it to a dungeon (can be the same dungeon, or another one).
     */
    public function copyMappingVersionToDungeon(MappingVersion $sourceMappingVersion, Dungeon $dungeon): MappingVersion;

    /**
     * Takes an existing mapping version's contents and applies it to another mapping version.
     */
    public function copyMappingVersionContentsToDungeon(
        MappingVersion $sourceMappingVersion,
        MappingVersion $targetMappingVersion,
    ): MappingVersion;

    /**
     * Gets a mapping version of a dungeon, or creates a new one for this dungeon if the most recent version has been pushed.
     *
     * This is useful for when the mapping changes - it determines if we need to insert a new version or not.
     *
     * @return void
     */
    public function getMappingVersionOrNew(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion;
}
