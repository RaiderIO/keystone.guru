<?php

namespace App\Service\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use Illuminate\Support\Collection;

interface DungeonServiceInterface
{
    public function importInstanceIdsFromCsv(string $filePath): bool;

    public function getDungeonContext(?User $user = null): Dungeon;

    public function setDungeonContext(
        Dungeon $dungeon,
        ?User   $user = null,
    ): void;

    /**
     * @return Collection<int, Dungeon>
     */
    public function getDungeonsForGameVersion(?GameVersion $gameVersion = null): Collection;

    /**
     * Cached headline stats for the dungeon overview: compendium counts plus pull/enemy stats for the
     * dungeon's current mapping version.
     *
     * @return array{npc: int, spell: int, pull_count: int, avg_enemies_per_pull: float}
     */
    public function getDungeonOverviewStats(Dungeon $dungeon, GameVersion $gameVersion): array;
}
