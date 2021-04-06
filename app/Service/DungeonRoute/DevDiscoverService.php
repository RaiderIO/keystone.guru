<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DevDiscoverService implements DiscoverServiceInterface
{
    /** @var Closure|null */
    private ?Closure $_closure = null;

    /**
     * @inheritDoc
     */
    function withBuilder(Closure $closure): DiscoverServiceInterface
    {
        $this->_closure = $closure;

        return $this;
    }

    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @return Builder
     */
    private function popularBuilder(): Builder
    {
        return DungeonRoute::query()->limit(10)
            ->when($this->_closure !== null, $this->_closure)
            ->with(['author', 'affixes', 'ratings'])
//            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->where('demo', false);
    }

    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @return Builder
     */
    private function newBuilder(): Builder
    {
        return DungeonRoute::query()->limit(10)
            ->when($this->_closure !== null, $this->_closure)
            ->with(['author', 'affixes', 'ratings'])
//            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->whereNull('dungeon_routes.expires_at')
            ->where('demo', false)
            ->orderBy('published_at', 'desc');
    }

    /**
     * @inheritDoc
     */
    function popular(): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * @inheritDoc
     */
    function popularGroupedByDungeon(): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * @inheritDoc
     */
    function popularByAffixGroup(AffixGroup $affixGroup): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * @inheritDoc
     */
    function popularGroupedByDungeonByAffixGroup(AffixGroup $affixGroup): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * @inheritDoc
     */
    function popularByDungeon(Dungeon $dungeon): Collection
    {
        return $this->popularBuilder()->get();
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
    function new(): Collection
    {
        return $this->newBuilder()->get();
    }

    /**
     * @inheritDoc
     */
    function newByAffixGroup(AffixGroup $affixGroup): Collection
    {
        return $this->newBuilder()->get();
    }

    /**
     * @inheritDoc
     */
    function newByDungeon(Dungeon $dungeon): Collection
    {
        return $this->newBuilder()->get();
    }

    /**
     * @inheritDoc
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection
    {
        return $this->newBuilder()->get();
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