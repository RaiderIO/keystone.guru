<?php

namespace App\Service\Dungeon;

use App\Models\Dungeon;
use App\Models\User;

interface DungeonServiceInterface
{
    public function importInstanceIdsFromCsv(string $filePath): bool;

    public function getDungeonContext(?User $user = null): Dungeon;

    public function setDungeonContext(
        Dungeon $dungeon,
        ?User   $user = null,
    ): void;
}
