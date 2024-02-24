<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup\AffixGroupBase;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Season;
use Closure;
use Illuminate\Support\Collection;

interface DiscoverServiceInterface
{
    /**
     * @return $this
     */
    function withLimit(int $limit): self;

    /**
     * @return $this
     */
    function withBuilder(Closure $closure): self;

    /**
     * @param Season|null $season
     * @return $this
     */
    function withSeason(?Season $season): self;

    /**
     * @return $this
     */
    function withExpansion(Expansion $expansion): self;

    /**
     * @return $this
     */
    function withCache(bool $enabled): self;

    /*
    |--------------------------------------------------------------------------
    | DungeonRoutes
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of DungeonRoutes.
    |
    */
    /**
     * @return Collection
     */
    function popular(): Collection;

    /**
     * @return Collection
     */
    function popularGroupedByDungeon(): Collection;

    /**
     * @return Collection
     */
    function popularByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /**
     * @return Collection
     */
    function popularGroupedByDungeonByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /**
     * @return Collection
     */
    function popularByDungeon(Dungeon $dungeon): Collection;

    /**
     * @return Collection
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;

    /**
     * @return Collection
     */
    function popularBySeason(Season $season): Collection;

    /**
     * @return Collection
     */
    function popularBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection;

    /**
     * @return Collection
     */
    function new(): Collection;

    /**
     * @return Collection
     */
    function newByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /**
     * @return Collection
     */
    function newByDungeon(Dungeon $dungeon): Collection;

    /**
     * @return Collection
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;

    /**
     * @return Collection
     */
    function newBySeason(Season $season): Collection;

    /**
     * @return Collection
     */
    function newBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection;

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of users
    |
    */
    /**
     * @return Collection
     */
    function popularUsers(): Collection;

    /**
     * @return Collection
     */
    function popularUsersByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /**
     * @return Collection
     */
    function popularUsersByDungeon(Dungeon $dungeon): Collection;

    /**
     * @return Collection
     */
    function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;
}
