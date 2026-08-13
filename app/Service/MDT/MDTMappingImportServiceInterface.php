<?php

namespace App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use Exception;

interface MDTMappingImportServiceInterface
{
    public function importMappingVersionFromMDT(
        MappingServiceInterface $mappingService,
        Dungeon                 $dungeon,
        ?GameVersion            $gameVersion = null,
        bool                    $forceImport = false,
    ): MappingVersion;

    public function getMDTMappingHash(Dungeon $dungeon): string;

    /**
     * @param array<int, Exception> $failures Appended with any exception a per-NPC save legitimately failed
     *                                        with - everything except the benign "already imported by
     *                                        another dungeon" unique-constraint case. Only
     *                                        importMappingVersionFromMDT() inspects this afterwards to
     *                                        decide whether the whole import must fail (#3755); the
     *                                        standalone mdt:importnpcs command leaves it at its default and
     *                                        keeps behaving as before.
     */
    public function importNpcsDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon, GameVersion $gameVersion, array &$failures = []): void;

    public function importSpellDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon): void;
}
