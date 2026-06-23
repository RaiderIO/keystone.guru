<?php

namespace App\Service\DungeonRoute;

use App\Models\AffixGroup\AffixGroupBase;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
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
    public function withExpansion(Expansion $expansion): DiscoverServiceInterface;

    /**
     * @return $this
     */
    public function withGameVersion(GameVersion $gameVersion): DiscoverServiceInterface;

    /**
     * @return $this
     */
    public function withCache(bool $enabled): self;

    /**
     * @return $this
     */
    public function excludeTeam(?Team $team): self;

    /*
    |--------------------------------------------------------------------------
    | DungeonRoutes
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of DungeonRoutes.
    |
    */

    /** @return Collection<int, mixed> */
    public function popular(): Collection;

    /** @return Collection<string, mixed> */
    public function popularGroupedByDungeon(): Collection;

    /** @return Collection<int, mixed> */
    public function popularByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /** @return Collection<string, mixed> */
    public function popularGroupedByDungeonByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /** @return Collection<int, mixed> */
    public function popularByDungeon(Dungeon $dungeon): Collection;

    /** @return Collection<int, mixed> */
    public function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;

    /** @return Collection<int, mixed> */
    public function popularBySeason(Season $season): Collection;

    /** @return Collection<int, mixed> */
    public function popularBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection;

    /** @return Collection<int, mixed> */
    public function new(): Collection;

    /** @return Collection<int, mixed> */
    public function newByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /** @return Collection<int, mixed> */
    public function newByDungeon(Dungeon $dungeon): Collection;

    /** @return Collection<int, mixed> */
    public function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;

    /** @return Collection<int, mixed> */
    public function newBySeason(Season $season): Collection;

    /** @return Collection<int, mixed> */
    public function newBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection;

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | The result of these queries should be a list of users
    |
    */

    /** @return Collection<int, mixed> */
    public function popularUsers(): Collection;

    /** @return Collection<int, mixed> */
    public function popularUsersByAffixGroup(AffixGroupBase $affixGroup): Collection;

    /** @return Collection<int, mixed> */
    public function popularUsersByDungeon(Dungeon $dungeon): Collection;

    /** @return Collection<int, mixed> */
    public function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection;
}
