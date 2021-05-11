<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Service\Expansion\ExpansionService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DevDiscoverService implements DiscoverServiceInterface
{
    /** @var Closure|null */
    private ?Closure $closure = null;

    /** @var ExpansionService  */
    private ExpansionService $expansionService;

    /**
     * DevDiscoverService constructor.
     * @param ExpansionService $expansionService
     */
    public function __construct(ExpansionService $expansionService)
    {
        $this->expansionService = $expansionService;
    }

    /**
     * @inheritDoc
     */
    function withBuilder(Closure $closure): DiscoverServiceInterface
    {
        $this->closure = $closure;

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
            ->when($this->closure !== null, $this->closure)
            ->select('dungeon_routes.*')
            ->with(['author', 'affixes', 'ratings'])
            ->without(['faction', 'specializations', 'classes', 'races'])
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->where('dungeons.expansion_id', $this->expansionService->getCurrentExpansion()->id)
//            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->whereNull('dungeon_routes.expires_at')
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
            ->when($this->closure !== null, $this->closure)
            ->select('dungeon_routes.*')
            ->with(['author', 'affixes', 'ratings'])
            ->without(['faction', 'specializations', 'classes', 'races'])
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->where('dungeons.expansion_id', $this->expansionService->getCurrentExpansion()->id)
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