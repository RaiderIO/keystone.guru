<?php

namespace App\Service\MDT;

use App\Models\Mapping\MappingVersion;

interface MDTMappingExportServiceInterface
{
    /**
     * @param MappingVersion $mappingVersion
     * @return string
     */
    public function getMDTMappingAsLuaString(MappingVersion $mappingVersion): string;
}
