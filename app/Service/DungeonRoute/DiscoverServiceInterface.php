<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface DiscoverServiceInterface
{
    /**
     * @param Closure $closure
     * @return $this
     */
    function withBuilder(Closure $closure): self;

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
     * @param AffixGroup $affixGroup
     * @return Collection
     */
    function popularByAffixGroup(AffixGroup $affixGroup): Collection;

    /**
     * @param AffixGroup $affixGroup
     * @return Collection
     */
    function popularGroupedByDungeonByAffixGroup(AffixGroup $affixGroup): Collection;

    /**
     * @param Dungeon $dungeon
     * @return Collection
     */
    function popularByDungeon(Dungeon $dungeon): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroup $affixGroup
     * @return Collection
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection;

    /**
     * @return Collection
     */
    function new(): Collection;

    /**
     * @param AffixGroup $affixGroup
     * @return Collection
     */
    function newByAffixGroup(AffixGroup $affixGroup): Collection;

    /**
     * @param Dungeon $dungeon
     * @return Collection
     */
    function newByDungeon(Dungeon $dungeon): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroup $affixGroup
     * @return Collection
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection;

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
     * @param AffixGroup $affixGroup
     * @return Collection
     */
    function popularUsersByAffixGroup(AffixGroup $affixGroup): Collection;

    /**
     * @param Dungeon $dungeon
     * @return Collection
     */
    function popularUsersByDungeon(Dungeon $dungeon): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroup $affixGroup
     * @return Collection
     */
    function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection;
}