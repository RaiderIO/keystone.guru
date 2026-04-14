<?php

namespace App\Service\CombatLog;

use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;

interface CombatLogMappingVersionServiceInterface
{
    public function createMappingVersionFromChallengeMode(
        string      $filePath,
        GameVersion $gameVersion,
    ): ?MappingVersion;

    public function createMappingVersionFromDungeonOrRaid(
        string          $filePath,
        GameVersion     $gameVersion,
        ?MappingVersion $mappingVersion = null,
        bool            $enemyConnections = false,
    ): ?MappingVersion;
}
