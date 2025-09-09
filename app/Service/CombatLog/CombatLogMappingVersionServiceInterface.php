<?php

namespace App\Service\CombatLog;

use App\Models\Mapping\MappingVersion;

interface CombatLogMappingVersionServiceInterface
{
    public function createMappingVersionFromChallengeMode(string $filePath): ?MappingVersion;

    public function createMappingVersionFromDungeonOrRaid(
        string          $filePath,
        ?MappingVersion $mappingVersion = null,
        bool            $enemyConnections = false
    ): ?MappingVersion;
}
