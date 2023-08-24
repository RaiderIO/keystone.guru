<?php


namespace App\Service\DungeonRoute;

use App\Models\AffixGroup\AffixGroupBase;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\PublishedState;
use App\Models\Season;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class DiscoverService extends BaseDiscoverService
{
    /**
     * @param string $key
     * @return string
     */
    private function getCacheKey(string $key): string
    {
        $this->ensureExpansion();

        if ($this->season !== null) {
            return sprintf('discover:%s:season-%s:%s:%d', $this->expansion->shortname, $this->season->index, $key, $this->limit);
        } else {
            return sprintf('discover:%s:%s:%d', $this->expansion->shortname, $key, $this->limit);
        }
    }

    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @return Builder
     */
    private function popularBuilder(): Builder
    {
        $this->ensureExpansion();

        // Grab affixes from either the set season, the current season of the expansion, or otherwise empty
        $currentSeasonAffixGroups = optional($this->season)->affixgroups ??
            optional($this->expansionService->getCurrentSeason($this->expansion))->affixgroups ??
            collect();

        return DungeonRoute::query()
            ->selectRaw('DISTINCT `dungeon_routes`.*')
            ->limit($this->limit)
            ->when($this->closure !== null, $this->closure)
            ->with(['author', 'affixes', 'ratings', 'mappingVersion'])
            ->without(['faction', 'specializations', 'classes', 'races'])
            // This query makes sure that routes which are 'catch all' for affixes drop down since they aren't as specific
            // as routes who only have say 1 or 2 affixes assigned to them.
            // It also applies a big penalty for routes that do not belong to the current season
            ->when($currentSeasonAffixGroups->isNotEmpty(), function (Builder $builder) use ($currentSeasonAffixGroups) {
                $builder
                    ->selectRaw(
                        sprintf(
                            '
                            dungeon_routes.*, dungeon_routes.popularity * (13 - (
                                SELECT IF(COUNT(*) = 0, 13, COUNT(*))
                                FROM dungeon_route_affix_groups
                                WHERE dungeon_route_id = `dungeon_routes`.`id`
                                AND affix_group_id BETWEEN %d AND %d
                            )) as weightedPopularity',
                            $currentSeasonAffixGroups->first()->id, $currentSeasonAffixGroups->last()->id
                        )
                    )
                    ->orderBy('weightedPopularity', 'desc');
            })
            ->join('dungeons', 'dungeons.id', 'dungeon_routes.dungeon_id')
            ->join('mapping_versions', 'mapping_versions.id', 'dungeon_routes.mapping_version_id')
            // Order by affix group ID in case of old seasons where all weightedPopularity will end up being 0.
            // We want the most recent season's routes showing up for this if possible
            ->joinSub(
                DungeonRouteAffixGroup::query()
                    ->selectRaw('dungeon_route_id, MAX(affix_group_id)')
                    ->groupBy('dungeon_route_id'),
                'ag', function (JoinClause $joinClause) {
                $joinClause->on('ag.dungeon_route_id', '=', 'dungeon_routes.id');
            })
            ->when($this->season === null, function (Builder $builder) {
                $builder->where('dungeons.expansion_id', $this->expansion->id);
            })
            ->when($this->season !== null, function (Builder $builder) {
                $builder->join('season_dungeons', 'season_dungeons.dungeon_id', '=', 'dungeons.id')
                    ->where('season_dungeons.season_id', $this->season->id);
            })
            ->where('dungeons.active', true)
            ->where('dungeon_routes.published_state_id', PublishedState::ALL[PublishedState::WORLD])
            ->whereNull('dungeon_routes.expires_at')
            ->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required)')
//            ->where('dungeon_routes.demo', false)
            ->groupBy('dungeon_routes.id');
    }

    /**
     * Gets a builder that provides a template for popular routes.
     *
     * @return Builder
     */
    private function newBuilder(): Builder
    {
        $this->ensureExpansion();

        return DungeonRoute::query()->limit($this->limit)
            ->when($this->closure !== null, $this->closure)
            ->with(['author', 'affixes', 'ratings', 'mappingVersion'])
            ->without(['faction', 'specializations', 'classes', 'races'])
            ->select('dungeon_routes.*')
            ->join('dungeons', 'dungeons.id', 'dungeon_routes.dungeon_id')
            ->join('mapping_versions', 'mapping_versions.id', 'dungeon_routes.mapping_version_id')
            ->when($this->season === null, function (Builder $builder) {
                $builder->where('dungeons.expansion_id', $this->expansion->id);
            })
            ->when($this->season !== null, function (Builder $builder) {
                $builder->join('season_dungeons', 'season_dungeons.dungeon_id', 'dungeons.id')
                    ->where('season_dungeons.season_id', $this->season->id);
            })
            ->where('dungeons.active', true)
            ->where('dungeon_routes.published_state_id', PublishedState::ALL[PublishedState::WORLD])
            ->whereNull('dungeon_routes.expires_at')
            ->whereRaw('IF(dungeon_routes.teeming, dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required_teeming,
                                    dungeon_routes.enemy_forces >= mapping_versions.enemy_forces_required)')
//            ->where('dungeon_routes.demo', false)
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
            return $this->popularBuilder()->get();
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
                $activeDungeons = ($this->season !== null ? $this->season->dungeons() : $this->expansion->dungeons())->active()->get();
                foreach ($activeDungeons as $dungeon) {
                    // Limit the amount of results of our queries
                    $result = $result->merge(
                        collect([
                            __($dungeon->name) => $this->withLimit(config('keystoneguru.discover.limits.per_dungeon'))->popularByDungeon($dungeon),
                        ])
                    );
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
                $activeDungeons = ($this->season !== null ? $this->season->dungeons() : $this->expansion->dungeons())->active()->get();
                foreach ($activeDungeons as $dungeon) {
                    // Limit the amount of results of our queries
                    $result = $result->merge(
                        collect([
                            __($dungeon->name) => $this->withLimit(config('keystoneguru.discover.limits.per_dungeon'))->popularByDungeonAndAffixGroup($dungeon, $affixGroup),
                        ])
                    );
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
                    ->where('dungeon_routes.dungeon_id', $dungeon->id)
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
                        ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
                        ->where('dungeon_routes.dungeon_id', $dungeon->id)
                        ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
                )->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @param Season $season
     * @return Collection
     */
    function popularBySeason(Season $season): Collection
    {
        $this->withSeason($season);

        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey('popular'), function () use ($season) {
                return $this->popularBuilder()
                    ->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @param Season $season
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function popularBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection
    {
        $this->withSeason($season);

        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('affix_group_%s:popular', $affixGroup->id)),
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
                    ->where('dungeon_routes.dungeon_id', $dungeon->id)
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
                    ->where('dungeon_routes.dungeon_id', $dungeon->id)
                    ->join('dungeon_route_affix_groups', 'dungeon_routes.id', '=', 'dungeon_route_affix_groups.dungeon_route_id')
                    ->where('dungeon_route_affix_groups.affix_group_id', $affixGroup->id)
                    ->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @param Season $season
     * @return Collection
     */
    function newBySeason(Season $season): Collection
    {
        $this->withSeason($season);

        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey('new'), function () {
                return $this->newBuilder()
                    ->get();
            }, config('keystoneguru.discover.service.popular.ttl')
        );
    }

    /**
     * @param Season $season
     * @param AffixGroupBase $affixGroup
     * @return Collection
     */
    function newBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection
    {
        $this->withSeason($season);

        return $this->cacheService->rememberWhen($this->closure === null,
            $this->getCacheKey(sprintf('affix_group_%s:new', $affixGroup->id)),
            function () use ($affixGroup) {
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
