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
     * The ids of the most viewed quarter of $dungeons, judged by the page views of the last
     * `keystoneguru.page_views.popular_dungeons_days` days. A dungeon nobody viewed is never among them.
     *
     * @param  Collection<int, Dungeon> $dungeons
     * @return Collection<int, int>
     */
    public function getPopularDungeonIds(Collection $dungeons): Collection;
}
