<?php

namespace App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTNpc;
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
        return
            md5(
                (new MDTDungeon($dungeon))
                    ->getMDTNPCs()
                    ->toJson()
            );
    }
}
