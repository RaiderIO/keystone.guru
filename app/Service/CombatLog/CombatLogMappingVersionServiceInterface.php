<?php

namespace App\Service\CombatLog;

use App\Models\Mapping\MappingVersion;

interface CombatLogMappingVersionServiceInterface
{
    /**
     * @param string $filePath
     * @return MappingVersion|null
     */
    public function createMappingVersionFromChallengeMode(string $filePath): ?MappingVersion;


    /**
     * @param string              $filePath
     * @param MappingVersion|null $mappingVersion
     * @return MappingVersion|null
     */
    public function createMappingVersionFromDungeonOrRaid(string $filePath, ?MappingVersion $mappingVersion = null): ?MappingVersion;
}
