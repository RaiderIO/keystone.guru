<?php

namespace App\Service\MDT;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;

interface MDTMappingImportServiceInterface
{
    /**
     * @param MappingServiceInterface $mappingService
     * @param Dungeon $dungeon
     * @param bool $forceImport
     * @return MappingVersion
     */
    public function importMappingVersionFromMDT(MappingServiceInterface $mappingService, Dungeon $dungeon, bool $forceImport = false): MappingVersion;

    /**
     * @param Dungeon $dungeon
     * @return string
     */
    public function getMDTMappingHash(Dungeon $dungeon): string;
}
