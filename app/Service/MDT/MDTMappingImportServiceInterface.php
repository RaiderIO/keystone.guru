<?php

namespace App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;

interface MDTMappingImportServiceInterface
{
    public function importMappingVersionFromMDT(
        MappingServiceInterface $mappingService,
        Dungeon                 $dungeon,
        ?GameVersion            $gameVersion = null,
        bool                    $forceImport = false,
    ): MappingVersion;

    public function getMDTMappingHash(Dungeon $dungeon): string;

    public function importNpcsDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon, GameVersion $gameVersion): void;

    public function importSpellDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon): void;
}
