<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

interface DiscoverServiceInterface
{
    /*
    |--------------------------------------------------------------------------
    | DungeonRoutes
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of DungeonRoutes.
    |
    */
    /**
     * @param int $limit
     * @return Collection
     */
    function popular(int $limit = 10): Collection;

    /**
     * @param AffixGroup $affixGroup
     * @param int $limit
     * @return Collection
     */
    function popularByAffixGroup(AffixGroup $affixGroup, int $limit = 10): Collection;

    /**
     * @param Dungeon $dungeon
     * @param int $limit
     * @return Collection
     */
    function popularByDungeon(Dungeon $dungeon, int $limit = 10): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroup $affixGroup
     * @param int $limit
     * @return Collection
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup, int $limit = 10): Collection;

    /**
     * @param int $limit
     * @return Collection
     */
    function new(int $limit = 10): Collection;

    /**
     * @param AffixGroup $affixGroup
     * @param int $limit
     * @return Collection
     */
    function newByAffixGroup(AffixGroup $affixGroup, int $limit = 10): Collection;

    /**
     * @param Dungeon $dungeon
     * @param int $limit
     * @return Collection
     */
    function newByDungeon(Dungeon $dungeon, int $limit = 10): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroup $affixGroup
     * @param int $limit
     * @return Collection
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup, int $limit = 10): Collection;

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of users
    |
    */
    /**
     * @param int $limit
     * @return Collection
     */
    function popularUsers(int $limit = 10): Collection;

    /**
     * @param AffixGroup $affixGroup
     * @param int $limit
     * @return Collection
     */
    function popularUsersByAffixGroup(AffixGroup $affixGroup, int $limit = 10): Collection;

    /**
     * @param Dungeon $dungeon
     * @param int $limit
     * @return Collection
     */
    function popularUsersByDungeon(Dungeon $dungeon, int $limit = 10): Collection;

    /**
     * @param Dungeon $dungeon
     * @param AffixGroup $affixGroup
     * @param int $limit
     * @return Collection
     */
    function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup, int $limit = 10): Collection;
}