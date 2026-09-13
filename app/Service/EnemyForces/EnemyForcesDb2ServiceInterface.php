<?php

namespace App\Service\EnemyForces;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\EnemyForces\Dtos\DungeonEnemyForcesDiff;
use App\Service\EnemyForces\Dtos\EnemyForcesDb2Report;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use InvalidArgumentException;

/**
 * Reads the M+ enemy forces the game client itself awards - the challenge mode scenario's criteria tree -
 * and compares them to the ones we imported from MDT, or writes them over ours.
 */
interface EnemyForcesDb2ServiceInterface
{
    /**
     * Diff a game build's enemy forces against the ones stored on our current mapping versions. Nothing is
     * written; the result is a report to look at.
     *
     * @param string       $product the CDN product to read, e.g. `wow` for retail or `wowt` for the PTR
     * @param string|null  $build   a specific build, or null for the most recent one wago.tools has
     * @param Dungeon|null $dungeon limits the diff to a single dungeon
     *
     * @throws WagoToolsDownloadException
     */
    public function diffEnemyForces(
        string      $product,
        GameVersion $gameVersion,
        ?string     $build = null,
        ?Dungeon    $dungeon = null,
    ): ?EnemyForcesDb2Report;

    /**
     * Take the client's enemy forces for one dungeon onto its mapping version, in place: the required total
     * and every NPC in {@see DungeonEnemyForcesDiff::getNpcDiffsToWrite()}. Nothing is deleted, and teeming,
     * shrouded and per-enemy overrides are left alone - the client has no source for them.
     *
     * @throws InvalidArgumentException when the diff is not resolved
     */
    public function writeEnemyForces(DungeonEnemyForcesDiff $dungeonDiff): void;
}
