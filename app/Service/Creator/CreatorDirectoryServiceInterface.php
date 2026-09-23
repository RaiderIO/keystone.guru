<?php

namespace App\Service\Creator;

use App\Models\Dungeon;
use App\Models\Season;
use App\Models\User;
use App\Service\Creator\Dtos\CreatorStats;
use App\Service\Creator\Enums\CreatorDirectorySort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CreatorDirectoryServiceInterface
{
    /**
     * A page of listed creators, their season figures counted for getStatsSeason().
     *
     * @param string|null $search     Optional case-insensitive match on the creator's name.
     * @param int|null    $categoryId Optional DungeonRouteCollectionCategory to filter on: only
     *                                creators who publicly share a collection of that kind.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateCreators(
        ?string              $search = null,
        ?int                 $categoryId = null,
        CreatorDirectorySort $sort = CreatorDirectorySort::ActiveThisSeason,
        ?int                 $perPage = null,
    ): LengthAwarePaginator;

    /**
     * The creators to feature in the rail on a dungeon's route page: listed creators ranked by the
     * popularity of their routes for that dungeon in its current season (all its routes when the
     * dungeon is in no current season). Empty when fewer than featured_min_count qualify.
     *
     * @return Collection<int, User>
     */
    public function getFeaturedCreators(Dungeon $dungeon, ?int $limit = null): Collection;

    public function getCreatorStats(User $user): CreatorStats;

    /**
     * The season "this season" means on the directory and profiles: the current season of the
     * viewer's game version, or null when that game version has no seasons.
     */
    public function getStatsSeason(): ?Season;
}
