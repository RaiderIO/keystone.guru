<?php

namespace App\Service\MDT;

use App\Models\Mapping\MappingVersion;
use App\Service\MDT\Exceptions\LuaParseException;

interface MDTMappingExportServiceInterface
{
    /**
     * @param string|null $preserveFromFilePath An existing MDT .lua file to carry MDT owned content over
     *                                          from - see {@see MDTMappingExportPreservedContent}.
     * @param bool        $regenerateMapPOIs    Rebuild the map POIs from our own map icons instead of
     *                                          keeping MDT's curated ones.
     *
     * @throws LuaParseException
     */
    public function getMDTMappingAsLuaString(
        MappingVersion $mappingVersion,
        bool           $excludeTranslations = false,
        bool           $forceEnemyPatrols = false,
        ?string        $preserveFromFilePath = null,
        bool           $regenerateMapPOIs = false,
    ): string;
}
