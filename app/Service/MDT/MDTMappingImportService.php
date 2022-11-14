<?php

namespace App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Mapping\MappingVersion;
use Exception;

class MDTMappingImportService implements MDTMappingImportServiceInterface
{

    /**
     * @inheritDoc
     */
    public function getMappingVersion(string $dungeon): MappingVersion
    {
        $result = null;

        // (new MDTDungeon($dungeon)

        return $result;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMDTMappingHash(string $dungeon): string
    {
        return md5((new MDTDungeon($dungeon))
            ->getMDTNPCs()
            ->toJson());
    }
}
