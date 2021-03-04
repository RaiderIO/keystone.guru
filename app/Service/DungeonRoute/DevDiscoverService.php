<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DevDiscoverService implements DiscoverServiceInterface
{
    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @param int $limit
     * @return Builder
     */
    private function popularBuilder(int $limit = 10): Builder
    {
        return DungeonRoute::query()
//            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->where('demo', false)
            ->limit($limit);
    }

    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @param int $limit
     * @return Builder
     */
    private function newBuilder(int $limit = 10): Builder
    {
        return DungeonRoute::query()
//            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->whereNull('dungeon_routes.expires_at')
            ->where('demo', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    /**
     * @inheritDoc
     */
    function popular(int $limit = 10): Collection
    {
        return $this->popularBuilder($limit)->get();
    }

    /**
     * @inheritDoc
     */
    function popularByAffixGroup(AffixGroup $affixGroup, int $limit = 10): Collection
    {
        return $this->popularBuilder($limit)->get();
    }

    /**
     * @inheritDoc
     */
    function popularByDungeon(Dungeon $dungeon, int $limit = 10): Collection
    {
        return $this->popularBuilder($limit)->get();
    }

    /**
     * @inheritDoc
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup, int $limit = 10): Collection
    {
        return $this->popularBuilder($limit)->get();
    }

    /**
     * @inheritDoc
     */
    function new(int $limit = 10): Collection
    {
        return $this->newBuilder($limit)->get();
    }

    /**
     * @inheritDoc
     */
    function newByAffixGroup(AffixGroup $affixGroup, int $limit = 10): Collection
    {
        return $this->newBuilder($limit)->get();
    }

    /**
     * @inheritDoc
     */
    function newByDungeon(Dungeon $dungeon, int $limit = 10): Collection
    {
        return $this->newBuilder($limit)->get();
    }

    /**
     * @inheritDoc
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup, int $limit = 10): Collection
    {
        return $this->newBuilder($limit)->get();
    }

    /**
     * @inheritDoc
     */
    function popularUsers(int $limit = 10): Collection
    {
        // TODO: Implement popularUsers() method.
    }

    /**
     * @inheritDoc
     */
    function popularUsersByAffixGroup(AffixGroup $affixGroup, int $limit = 10): Collection
    {
        // TODO: Implement popularUsersByAffixGroup() method.
    }

    /**
     * @inheritDoc
     */
    function popularUsersByDungeon(Dungeon $dungeon, int $limit = 10): Collection
    {
        // TODO: Implement popularUsersByDungeon() method.
    }

    /**
     * @inheritDoc
     */
    function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup, int $limit = 10): Collection
    {
        // TODO: Implement popularUsersByDungeonAndAffixGroup() method.
    }
}