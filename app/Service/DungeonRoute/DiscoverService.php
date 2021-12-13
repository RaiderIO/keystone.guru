<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup\AffixGroupBase;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\PublishedState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DiscoverService extends BaseDiscoverService
{
    /**
     * @param string $key
     * @return string
     */
    private function getCacheKey(string $key): string
    {
        return sprintf('discover:%s:%s', $this->expansion->shortname, $key);
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
            ->with(['author', 'affixes', 'ratings'])
            ->without(['faction', 'specializations', 'classes', 'races'])
            // This query makes sure that routes which are 'catch all' for affixes drop down since they aren't as specific
            // as routes who only have say 1 or 2 affixes assigned to them.
            // It also applies a big penalty for routes that do not belong to the current season
            ->selectRaw(sprintf('dungeon_routes.*, dungeon_routes.popularity * (13 - (
                    SELECT IF(COUNT(*) = 0, 13, COUNT(*))
                    FROM dungeon_route_affix_groups
                    WHERE dungeon_route_id = `dungeon_routes`.`id`
                    AND affix_group_id >= %s
                )) as weightedPopularity', $this->seasonService->getCurrentSeason()->affixgroups->first()->id)
            )
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->where('dungeons.expansion_id', $this->expansion->id)
            ->where('dungeons.active', true)
            ->where('dungeon_routes.published_state_id', PublishedState::where('name', PublishedState::WORLD)->first()->id)
            ->whereNull('dungeon_routes.expires_at')
            ->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces > dungeons.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces > dungeons.enemy_forces_required)')
            ->where('dungeon_routes.demo', false)
            ->groupBy('dungeon_routes.id')
            ->orderBy('weightedPopularity', 'desc');
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
            ->with(['author', 'affixes', 'ratings'])
            ->without(['faction', 'specializations', 'classes', 'races'])
            ->select('dungeon_routes.*')
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->where('dungeons.expansion_id', $this->expansion->id)
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
        return $builder
            ->selectRaw('dungeon_routes.*, dungeon_routes.popularity * (13 - (
                    SELECT COUNT(*)
                    FROM dungeon_route_affix_groups
                    WHERE dungeon_route_id = `dungeon_routes`.`id`
                )) as weightedPopularity'
            )
            ->reorder()
            ->orderByRaw('weightedPopularity DESC');
    }

    /**
     * @inheritDoc
     */
    function popular(): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null, $this->getCacheKey('popular'), function () {
            return $this->popularBuilder()
                ->get();
        }, config('keystoneguru.discover.service.popular.ttl'));
    }

    /**
     * @inheritDoc
     */
    function popularGroupedByDungeon(): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey('grouped_by_dungeon:popular'), function () {
                $result = collect();

                /** @var Collection|Dungeon[] $activeDungeons */
                $activeDungeons = $this->expansion->dungeons()->active()->get();
                foreach ($activeDungeons as $dungeon) {
                    // Limit the amount of results of our queries to 2
                    $result = $result->merge($this->withBuilder(function (Builder $builder) {
                        $builder->limit(2);
                    })->popularByDungeon($dungeon));
                }

                return $result;
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function popularByAffixGroup(AffixGroupBase $affixGroup): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('affix_group_%d:popular', $affixGroup->id)),
            function () use ($affixGroup) {
                return $this->applyAffixGroupCountPenalty(
                    $this->popularBuilder()
                        ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
                        ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
                )->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function popularGroupedByDungeonByAffixGroup(AffixGroupBase $affixGroup): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('grouped_by_dungeon:affix_group_%d:popular', $affixGroup->id)),
            function () use ($affixGroup) {
                $result = collect();

                /** @var Collection|Dungeon[] $activeDungeons */
                $activeDungeons = $this->expansion->dungeons()->active()->get();
                foreach ($activeDungeons as $dungeon) {
                    // Limit the amount of results of our queries to 2
                    $result = $result->merge($this->withBuilder(function (Builder $builder) {
                        $builder->limit(2);
                    })->popularByDungeonAndAffixGroup($dungeon, $affixGroup));
                }

                return $result;
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function popularByDungeon(Dungeon $dungeon): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('%s:popular', $dungeon->key)), function () use ($dungeon) {
                return $this->popularBuilder()
                    ->where('dungeon_id', $dungeon->id)
                    ->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('%s:affix_group_%s:popular', $dungeon->key, $affixGroup->id)),
            function () use ($dungeon, $affixGroup) {
                return $this->applyAffixGroupCountPenalty(
                    $this->popularBuilder()
                        ->where('dungeon_id', $dungeon->id)
                        ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
                        ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
                )->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function new(): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey('new'), function () {
                return $this->newBuilder()->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function newByAffixGroup(AffixGroupBase $affixGroup): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('affix_group_%d:new', $affixGroup->id)), function () use ($affixGroup) {
                return $this->newBuilder()
                    ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
                    ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
                    ->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function newByDungeon(Dungeon $dungeon): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('%s:new', $dungeon->key)), function () use ($dungeon) {
                return $this->newBuilder()
                    ->where('dungeon_id', $dungeon->id)
                    ->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection
    {
        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('%s:affix_group_%s:new', $dungeon->key, $affixGroup->id)),
            function () use ($dungeon, $affixGroup) {
                return $this->newBuilder()
                    ->where('dungeon_id', $dungeon->id)
                    ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
                    ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
                    ->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @inheritDoc
     */
    function popularUsers(): Collection
    {
        // TODO: Implement popularUsers() method.
        return collect();
    }

    /**
     * @inheritDoc
     */
    function popularUsersByAffixGroup(AffixGroupBase $affixGroup): Collection
    {
        // TODO: Implement popularUsersByAffixGroup() method.
        return collect();
    }

    /**
     * @inheritDoc
     */
    function popularUsersByDungeon(Dungeon $dungeon): Collection
    {
        // TODO: Implement popularUsersByDungeon() method.
        return collect();
    }

    /**
     * @inheritDoc
     */
    function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection
    {
        // TODO: Implement popularUsersByDungeonAndAffixGroup() method.
        return collect();
    }
}
