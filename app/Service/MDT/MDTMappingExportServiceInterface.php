<?php

namespace App\Service\MDT;

use App\Models\Mapping\MappingVersion;

interface MDTMappingExportServiceInterface
{
    public function getMDTMappingAsLuaString(MappingVersion $mappingVersion): string;
}
