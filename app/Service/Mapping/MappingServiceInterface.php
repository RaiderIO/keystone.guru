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
     * Creates a new mapping version for a dungeon from an MDT import. Always created with a null
     * `mdt_mapping_hash` - the caller must set it once the MDT import has actually finished successfully
     * (#3737).
     *
     * $currentMappingVersion is the dungeon's existing mapping version for $gameVersion to clone
     * hash/version/curated-content (checkpoints/map icons/mountable areas/dungeon floor switch markers) from -
     * the caller must scope it to $gameVersion itself (e.g. via
     * Dungeon::getCurrentMappingVersionForGameVersion()) and pass null when there is genuinely none yet (the
     * dungeon's first-ever import for this game version), rather than an ambient/unrelated game version's
     * mapping version (#3757). Curated content legitimately starts empty in that case, but facade_enabled and
     * FloorUnions - the dungeon's PHYSICAL floor layout, not game-version-specific - are still inherited from
     * the dungeon's current mapping version in ANY game version, so a facade dungeon's first import for a new
     * game version doesn't silently degrade coordinate conversion to the identity transform.
     */
    public function createNewMappingVersionFromMDTMapping(
        Dungeon         $dungeon,
        GameVersion     $gameVersion,
        ?MappingVersion $currentMappingVersion,
    ): MappingVersion;

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
     * $sourceMappingVersion may be null when there is genuinely no predecessor to clone from (e.g. a dungeon's
     * first-ever mapping version for a game version) - the target then simply starts with none (#3757).
     *
     * @return array<int, int> Source checkpoint id => cloned checkpoint id, so the caller can re-link the
     *                         membership of the enemies it clones or imports afterwards.
     */
    public function copyEnemyForcesCheckpointsToMappingVersion(
        ?MappingVersion $sourceMappingVersion,
        MappingVersion  $targetMappingVersion,
    ): array;
}
