<?php

namespace App\Service\Dungeon;

interface DungeonServiceInterface
{
    public function importInstanceIdsFromCsv(string $filePath): bool;
}
