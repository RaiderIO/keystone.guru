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
     * @param int $limit
     * @return $this
     */
    function withLimit(int $limit): self;

    /**
     * @param Closure $closure
     * @return $this
     */
    function withBuilder(Closure $closure): self;

    /**
     * @param Season|null $season
     * @return $this
     */
    function withSeason(?Season $season): self;

    /**
     * @param Expansion $expansion
     * @return $this
     */
    function withExpansion(Expansion $expansion): self;

    /**
     * @param bool $enabled
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
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function popularByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /**
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function popularGroupedByDungeonByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /**
     * @param Dungeon $dungeon
     * @return Collection
     */
    function popularByDungeon(Dungeon $dungeon): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;

    /**
     * @return Collection
     */
    function new(): Collection;

    /**
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function newByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /**
     * @param Dungeon $dungeon
     * @return Collection
     */
    function newByDungeon(Dungeon $dungeon): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;

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
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function popularUsersByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /**
     * @param Dungeon $dungeon
     * @return Collection
     */
    function popularUsersByDungeon(Dungeon $dungeon): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;
}
