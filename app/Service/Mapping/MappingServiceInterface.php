<?php

namespace App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;

interface MappingServiceInterface
{
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
     * Resolves the mapping version that best matches an imported MDT string's `addonVersion`, so a route
     * imported from an older MDT string is attached to the mapping version of that MDT era (#3380). Falls
     * back to the dungeon's current mapping version when the string carries no/unknown/newer addonVersion.
     */
    public function getMappingVersionForMdtAddonVersion(Dungeon $dungeon, ?int $addonVersion, ?GameVersion $gameVersion = null): ?MappingVersion;

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
}
