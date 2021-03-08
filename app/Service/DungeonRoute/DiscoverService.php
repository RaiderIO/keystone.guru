<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\PublishedState;
use App\Service\Cache\CacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class DiscoverService implements DiscoverServiceInterface
{
    const POPULAR_DAYS = 7;
    const CACHE_PREFIX = 'discover';

    /** @var CacheService */
    private $_cacheService;

    /**
     * DiscoverService constructor.
     */
    public function __construct()
    {
        $this->_cacheService = App::make(CacheService::class);
    }


    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @param int $limit
     * @return Builder
     */
    private function popularBuilder(int $limit = 10): Builder
    {
        return DungeonRoute::query()
            ->with('author')
            ->selectRaw('dungeon_routes.*, COUNT(page_views.id) as views')
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->leftJoin('page_views', function (JoinClause $join)
            {
                $join->on('page_views.model_id', '=', 'dungeon_routes.id');
                $join->where('page_views.model_class', DungeonRoute::class);
            })
            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->whereNull('dungeon_routes.expires_at')
            ->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces > dungeons.enemy_forces_required_teeming, 
                                    dungeon_routes.enemy_forces > dungeons.enemy_forces_required)')
            ->whereDate('page_views.created_at', '>', now()->subDays(self::POPULAR_DAYS))
            ->groupBy('dungeon_routes.id')
            ->orderBy('views', 'desc')
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
            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->whereNull('dungeon_routes.expires_at')
            ->where('demo', false)
            ->orderBy('published_at', 'desc')
            ->limit($limit);
    }

    /**
     * Gets the cache key with the class prefix for a certain key
     *
     * @param string $key
     * @return string
     */
    private function cacheKey(string $key)
    {
        return sprintf('%s_%s', self::CACHE_PREFIX, $key);
    }

    /**
     * @inheritDoc
     */
    function dropCaches(): void
    {
        // $this->_cacheService->
    }

    /**
     * @inheritDoc
     */
    function popular(int $limit = 10): Collection
    {
        return $this->_cacheService->remember(
            $this->cacheKey(sprintf('popular_limit_%s', $limit)),
            $this->popularBuilder($limit)->get()
        );
    }

    /**
     * @inheritDoc
     */
    function popularByAffixGroup(AffixGroup $affixGroup, int $limit = 10): Collection
    {
        return $this->popularBuilder($limit)
            ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
            ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
            ->get();
    }

    /**
     * @inheritDoc
     */
    function popularByDungeon(Dungeon $dungeon, int $limit = 10): Collection
    {
        return $this->popularBuilder($limit)
            ->where('dungeon_id', $dungeon->id)
            ->get();
    }

    /**
     * @inheritDoc
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup, int $limit = 10): Collection
    {
        return $this->popularBuilder($limit)
            ->where('dungeon_id', $dungeon->id)
            ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
            ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
            ->get();
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
        return $this->newBuilder($limit)
            ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
            ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
            ->get();
    }

    /**
     * @inheritDoc
     */
    function newByDungeon(Dungeon $dungeon, int $limit = 10): Collection
    {
        return $this->newBuilder($limit)
            ->where('dungeon_id', $dungeon->id)
            ->get();
    }

    /**
     * @inheritDoc
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroup $affixGroup, int $limit = 10): Collection
    {
        return $this->newBuilder($limit)
            ->where('dungeon_id', $dungeon->id)
            ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
            ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
            ->get();
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