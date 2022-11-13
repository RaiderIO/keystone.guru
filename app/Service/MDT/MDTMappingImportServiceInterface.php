<?php

namespace App\Service\MDT;

use App\Models\Mapping\MappingVersion;

interface MDTMappingImportServiceInterface
{
    /**
     * @param string $dungeon
     * @return MappingVersion
     */
    public function getMappingVersion(string $dungeon): MappingVersion;

    /**
     * @param string $dungeon
     * @return string
     */
    public function getMDTMappingHash(string $dungeon): string;
}
