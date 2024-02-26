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
    public function withLimit(int $limit): self;

    /**
     * @return $this
     */
    public function withBuilder(Closure $closure): self;

    /**
     * @return $this
     */
    public function withSeason(?Season $season): self;

    /**
     * @return $this
     */
    public function withExpansion(Expansion $expansion): self;

    /**
     * @return $this
     */
    public function withCache(bool $enabled): self;

    /*
    |--------------------------------------------------------------------------
    | DungeonRoutes
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of DungeonRoutes.
    |
    */

    public function popular(): Collection;

    public function popularGroupedByDungeon(): Collection;

    public function popularByAffixGroup(AffixGroupBase $affixGroup): Collection;

    public function popularGroupedByDungeonByAffixGroup(AffixGroupBase $affixGroup): Collection;

    public function popularByDungeon(Dungeon $dungeon): Collection;

    public function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;

    public function popularBySeason(Season $season): Collection;

    public function popularBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection;

    public function new(): Collection;

    public function newByAffixGroup(AffixGroupBase $affixGroup): Collection;

    public function newByDungeon(Dungeon $dungeon): Collection;

    public function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;

    public function newBySeason(Season $season): Collection;

    public function newBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection;

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of users
    |
    */

    public function popularUsers(): Collection;

    public function popularUsersByAffixGroup(AffixGroupBase $affixGroup): Collection;

    public function popularUsersByDungeon(Dungeon $dungeon): Collection;

    public function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;
}
