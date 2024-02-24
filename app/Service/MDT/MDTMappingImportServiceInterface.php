<?php

namespace App\Service\MDT;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;

interface MDTMappingImportServiceInterface
{
    /**
     * @return MappingVersion
     */
    public function importMappingVersionFromMDT(MappingServiceInterface $mappingService, Dungeon $dungeon, bool $forceImport = false): MappingVersion;

    /**
     * @return string
     */
    public function getMDTMappingHash(Dungeon $dungeon): string;
}
