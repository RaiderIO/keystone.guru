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
     * Creates a new mapping version for a dungeon. Always created with a null `mdt_mapping_hash` -
     * the caller must set it once the MDT import has actually finished successfully (#3737).
     */
    public function createNewMappingVersionFromMDTMapping(Dungeon $dungeon, ?GameVersion $gameVersion): MappingVersion;

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
     *
     * Enemy forces checkpoints are NOT part of this: their members live in `enemies`, which this method does
     * not copy either, so the caller has to be able to re-link membership itself. Clone them with
     * copyEnemyForcesCheckpointsToMappingVersion() when the target should inherit them - only the MDT
     * re-import path does; a bare mapping version deliberately starts with none, since it has no enemies to
     * assign them to (#3702).
     */
    public function copyMappingVersionContentsToDungeon(
        MappingVersion $sourceMappingVersion,
        MappingVersion $targetMappingVersion,
    ): MappingVersion;

    /**
     * Clones a mapping version's enemy forces checkpoints into another mapping version, without their members -
     * enemies are cloned by another code path entirely, or (on an MDT mapping import) re-created from scratch.
     *
     * @return array<int, int> Source checkpoint id => cloned checkpoint id, so the caller can re-link the
     *                         membership of the enemies it clones or imports afterwards.
     */
    public function copyEnemyForcesCheckpointsToMappingVersion(
        MappingVersion $sourceMappingVersion,
        MappingVersion $targetMappingVersion,
    ): array;
}
