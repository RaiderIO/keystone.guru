<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use Ramsey\Collection\Collection;

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
    function popular(): Collection;

    function popularByAffixGroup(AffixGroup $affixGroup): Collection;

    function popularByDungeon(Dungeon $dungeon): Collection;

    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection;

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of users
    |
    */
    function popularUsers(): Collection;

    function popularUsersByAffixGroup(AffixGroup $affixGroup): Collection;

    function popularUsersByDungeon(Dungeon $dungeon): Collection;

    function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection;
}