<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\PublishedState;
use App\Service\Cache\CacheService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class DiscoverService implements DiscoverServiceInterface
{
    /** @var CacheService */
    private CacheService $_cacheService;

    /** @var Closure|null */
    private ?Closure $_closure = null;

    /**
     * DiscoverService constructor.
     */
    public function __construct()
    {
        $this->_cacheService = App::make(CacheService::class);
    }

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
            ->without(['faction', 'specializations', 'classes', 'races'])
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->where('dungeons.active', true)
            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->whereNull('dungeon_routes.expires_at')
            ->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces > dungeons.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces > dungeons.enemy_forces_required)')
            ->where('dungeon_routes.demo', false)
            ->groupBy('dungeon_routes.id')
            ->orderBy('dungeon_routes.popularity', 'desc');
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
            ->without(['faction', 'specializations', 'classes', 'races'])
            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->whereNull('dungeon_routes.expires_at')
            ->where('dungeon_routes.demo', false)
            ->orderBy('published_at', 'desc');
    }

    /**
     * Adds a penalty to the affix group count - more affixes assigned to your route will cause it to drop in popularity
     * to prevent having routes assigned to every affix from always dominating the rankings
     * @param Builder $builder
     * @return Builder
     */
    private function applyAffixGroupCountPenalty(Builder $builder): Builder
    {
        return $builder;
        // @TODO This doesn't work unfortunately - need to investigate further
//            ->selectRaw('COUNT(dungeon_route_affix_groups.id) as affixCount')
//            // Less affixes get a higher priority to encourage more specific routes
//            ->reorder()
//            // Having less affixes in your route will cause it to bubble up sooner for this specific week
//            ->orderByRaw('(13 - affixCount) * views');
    }

    /**
     * @inheritDoc
     */
    function popular(): Collection
    {
        return $this->popularBuilder()
            ->get();
    }

    /**
     * @inheritDoc
     */
    function popularGroupedByDungeon(): Collection
    {
        return $this->_cacheService->remember('discover_routes_popular', function ()
        {
            $result = collect();


            $activeDungeons = Dungeon::active()->get();
            foreach ($activeDungeons as $dungeon) {
                // Limit the amount of results of our queries to 2
                $result = $result->merge($this->withBuilder(function (Builder $builder)
                {
                    $builder->limit(2);
                })->popularByDungeon($dungeon));
            }

            return $result;
        }, config('keystoneguru.discover.service.popular.ttl'));
    }

    /**
     * @inheritDoc
     */
    function popularByAffixGroup(AffixGroup $affixGroup): Collection
    {
        return $this->applyAffixGroupCountPenalty(
            $this->popularBuilder()
                ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
                ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
        )->get();
    }

    /**
     * @inheritDoc
     */
    function popularGroupedByDungeonByAffixGroup(AffixGroup $affixGroup): Collection
    {
        return $this->_cacheService->remember(sprintf('discover_routes_popular_by_affix_group_%d', $affixGroup->id), function () use ($affixGroup)
        {
            $result = collect();

            $activeDungeons = Dungeon::active()->get();
            foreach ($activeDungeons as $dungeon) {
                // Limit the amount of results of our queries to 2
                $result = $result->merge($this->withBuilder(function (Builder $builder)
                {
                    $builder->limit(2);
                })->popularByDungeonAndAffixGroup($dungeon, $affixGroup));
            }

            return $result;
        }, config('keystoneguru.discover.service.popular.ttl'));
    }

    /**
     * @inheritDoc
     */
    function popularByDungeon(Dungeon $dungeon): Collection
    {
        return $this->popularBuilder()
            ->where('dungeon_id', $dungeon->id)
            ->get();
    }

    /**
     * @inheritDoc
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection
    {
        return $this->applyAffixGroupCountPenalty(
            $this->popularBuilder()
                ->where('dungeon_id', $dungeon->id)
                ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
                ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
        )->get();
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
        return $this->newBuilder()
            ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
            ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
            ->get();
    }

    /**
     * @inheritDoc
     */
    function newByDungeon(Dungeon $dungeon): Collection
    {
        return $this->newBuilder()
            ->where('dungeon_id', $dungeon->id)
            ->get();
    }

    /**
     * @inheritDoc
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup): Collection
    {
        return $this->newBuilder()
            ->where('dungeon_id', $dungeon->id)
            ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
            ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
            ->get();
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