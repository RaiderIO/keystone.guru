<?php

namespace App\Service\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;

interface DungeonServiceInterface
{
    public function importInstanceIdsFromCsv(string $filePath): bool;

    /**
     * The saved dungeon context, or - when it is not usable in the user's current game version - that game
     * version's default dungeon, which is then saved as the new context.
     */
    public function getDungeonContext(?User $user = null): Dungeon;

    /**
     * The dungeon to open for $gameVersion: the dungeon context when it is usable there and passes $filter,
     * otherwise the first such dungeon of the game version's dungeon list. Null when the game version has none.
     *
     * @param Closure(Dungeon): bool|null $filter
     */
    public function getDungeonContextForGameVersion(
        GameVersion $gameVersion,
        ?User       $user = null,
        ?Closure    $filter = null,
    ): ?Dungeon;

    /**
     * The first dungeon of the game version's dungeon list that is usable in it and passes $filter.
     *
     * @param Closure(Dungeon): bool|null $filter
     */
    public function getDefaultDungeon(GameVersion $gameVersion, ?Closure $filter = null): ?Dungeon;

    /**
     * Whether $dungeon is active and has a mapping for $gameVersion (or its parent game version).
     */
    public function isUsableInGameVersion(Dungeon $dungeon, GameVersion $gameVersion): bool;

    public function setDungeonContext(
        Dungeon $dungeon,
        ?User   $user = null,
    ): void;

    /**
     * @return Collection<int, Dungeon>
     */
    public function getDungeonsForGameVersion(?GameVersion $gameVersion = null): Collection;

    /**
     * The dungeons of $dungeons that only $gameVersion maps, none of them inherited from its parent game version.
     * Empty for a game version without a parent.
     *
     * @param  Collection<int, Dungeon> $dungeons
     * @return Collection<int, int>     Dungeon ids
     */
    public function getExclusiveDungeonIds(GameVersion $gameVersion, Collection $dungeons): Collection;

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
