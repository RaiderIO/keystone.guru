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
     * Every dungeon's views as a share of the most viewed one's, judged by the page views of the last
     * `keystoneguru.page_views.dungeon_views_days` days: 1 for the most viewed, 0 for one nobody viewed. Empty when
     * none of $dungeons was viewed at all.
     *
     * @param  Collection<int, Dungeon> $dungeons
     * @return Collection<int, float>   Keyed by dungeon id
     */
    public function getViewShares(Collection $dungeons): Collection;
}
