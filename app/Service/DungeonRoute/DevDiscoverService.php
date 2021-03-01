<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DevDiscoverService implements DiscoverServiceInterface
{
    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @return Builder
     */
    private function popularBuilder(): Builder
    {
        return DB::table('dungeon_routes')->limit(10);
    }

    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @return Builder
     */
    private function newBuilder(): Builder
    {
        return DB::table('dungeon_routes')->limit(10)->orderBy('created_at', 'desc');
    }

    /**
     * @inheritDoc
     */
    function popular(): Collection
    {
        return $this->popularBuilder()->get()->mapInto(DungeonRoute::class);
    }

    /**
     * @inheritDoc
     */
    function popularByAffixGroup(AffixGroup $affixGroup): Collection
    {
        return $this->popularBuilder()->get()->mapInto(DungeonRoute::class);
    }

    /**
     * @inheritDoc
     */
    function popularByDungeon(Dungeon $dungeon): Collection
    {
        return $this->popularBuilder()->get()->mapInto(DungeonRoute::class);
    }

    /**
     * @inheritDoc
     */
    function new(): Collection
    {
        return $this->newBuilder()->get()->mapInto(DungeonRoute::class);
    }

    /**
     * @inheritDoc
     */
    function newByAffixGroup(AffixGroup $affixGroup): Collection
    {
        return $this->newBuilder()->get()->mapInto(DungeonRoute::class);
    }

    /**
     * @inheritDoc
     */
    function newByDungeon(Dungeon $dungeon): Collection
    {
        return $this->newBuilder()->get()->mapInto(DungeonRoute::class);
    }

    /**
     * @inheritDoc
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection
    {
        return $this->newBuilder()->get()->mapInto(DungeonRoute::class);
    }


    /**
     * @inheritDoc
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * @inheritDoc
     */
    function popularUsers(): Collection
    {
        // TODO: Implement popularUsers() method.
    }

    /**
     * @inheritDoc
     */
    function popularUsersByAffixGroup(AffixGroup $affixGroup): Collection
    {
        // TODO: Implement popularUsersByAffixGroup() method.
    }

    /**
     * @inheritDoc
     */
    function popularUsersByDungeon(Dungeon $dungeon): Collection
    {
        // TODO: Implement popularUsersByDungeon() method.
    }

    /**
     * @inheritDoc
     */
    function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection
    {
        // TODO: Implement popularUsersByDungeonAndAffixGroup() method.
    }
}