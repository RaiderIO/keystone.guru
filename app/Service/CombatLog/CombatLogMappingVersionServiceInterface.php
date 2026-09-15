<?php

namespace App\Service\CombatLog;

use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Exceptions\DungeonHasNoNpcsException;

interface CombatLogMappingVersionServiceInterface
{
    /**
     * @throws DungeonHasNoNpcsException When the dungeon in the combat log has no NPCs attached to it yet.
     */
    public function createMappingVersionFromChallengeMode(
        string      $filePath,
        GameVersion $gameVersion,
    ): ?MappingVersion;

    /**
     * @throws DungeonHasNoNpcsException When the dungeon in the combat log has no NPCs attached to it yet.
     */
    public function createMappingVersionFromDungeonOrRaid(
        string          $filePath,
        GameVersion     $gameVersion,
        ?MappingVersion $mappingVersion = null,
        bool            $enemyConnections = false,
    ): ?MappingVersion;
}
