<?php

namespace App\Service\MDT;

use App\Models\Mapping\MappingVersion;

class MDTMappingService implements MDTMappingServiceInterface
{

    /**
     * @inheritDoc
     */
    public function getMDTMapping(MappingVersion $mappingVersion): string
    {
        // TODO: Implement getMDTMapping() method.
    }

    /**
     * @inheritDoc
     */
    public function getMappingVersion(string $dungeon): MappingVersion
    {
        // TODO: Implement getMappingVersion() method.
    }

    /**
     * @inheritDoc
     */
    public function getMDTMappingHash(string $dungeon): string
    {
        // TODO: Implement getMDTMappingHash() method.
    }
}
