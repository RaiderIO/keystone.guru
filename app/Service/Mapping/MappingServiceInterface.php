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
