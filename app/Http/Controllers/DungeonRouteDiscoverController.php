<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DungeonRouteDiscoverController extends Controller
{
    /**
     * @return Factory|View
     */
    public function search()
    {
        return view('dungeonroute.discover.search', [

        ]);
    }

    /**
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     */
    public function discover(DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.overview'));
        };

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'   => 'dungeonroutes.discover',
            'dungeonroutes' => [
                'thisweek' => $discoverService->popularGroupedByDungeonByAffixGroup($seasonService->getCurrentSeason()->getCurrentAffixGroup()),
                'nextweek' => $discoverService->popularGroupedByDungeonByAffixGroup($seasonService->getCurrentSeason()->getNextAffixGroup()),
                'new'      => $discoverService->withBuilder($closure)->new(),
                'popular'  => $discoverService->popularGroupedByDungeon(),
            ]
        ]);
    }

    /**
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     */
    public function discoverdungeon(Dungeon $dungeon, DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.overview'));
        };

        return view('dungeonroute.discover.dungeon.overview', [
            'dungeon'       => $dungeon,
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon',
            'dungeonroutes' => [
                'thisweek' => $discoverService->withBuilder($closure)->popularByDungeonAndAffixGroup($dungeon, $seasonService->getCurrentSeason()->getCurrentAffixGroup()),
                'nextweek' => $discoverService->withBuilder($closure)->popularByDungeonAndAffixGroup($dungeon, $seasonService->getCurrentSeason()->getNextAffixGroup()),
                'new'      => $discoverService->withBuilder($closure)->newByDungeon($dungeon),
                'popular'  => $discoverService->withBuilder($closure)->popularByDungeon($dungeon),
            ]
        ]);
    }

    /**
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     */
    public function discoverpopular(DiscoverServiceInterface $discoverService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.category', [
            'category'      => 'popular',
            'title'         => __('controller.dungeonroutediscover.popular'),
            'breadcrumbs'   => 'dungeonroutes.popular',
            'dungeonroutes' => $discoverService->withBuilder($closure)->popular(),
        ]);
    }

    /**
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     */
    public function discoverthisweek(DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.category', [
            'category'      => 'thisweek',
            'title'         => __('controller.dungeonroutediscover.this_week_affixes'),
            'breadcrumbs'   => 'dungeonroutes.thisweek',
            'dungeonroutes' => $discoverService->withBuilder($closure)->popularByAffixGroup(
                $seasonService->getCurrentSeason()->getCurrentAffixGroup()
            ),
            'affixgroup'    => $seasonService->getCurrentSeason()->getCurrentAffixGroup(),
        ]);
    }

    /**
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     * @throws Exception
     */
    public function discovernextweek(DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.category', [
            'category'      => 'nextweek',
            'title'         => __('controller.dungeonroutediscover.next_week_affixes'),
            'breadcrumbs'   => 'dungeonroutes.nextweek',
            'dungeonroutes' => $discoverService->withBuilder($closure)->popularByAffixGroup(
                $seasonService->getCurrentSeason()->getNextAffixGroup()
            ),
            'affixgroup'    => $seasonService->getCurrentSeason()->getCurrentAffixGroup(),
        ]);
    }

    /**
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     */
    public function discovernew(DiscoverServiceInterface $discoverService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.category', [
            'category'      => 'new',
            'title'         => __('controller.dungeonroutediscover.new'),
            'breadcrumbs'   => 'dungeonroutes.new',
            'dungeonroutes' => $discoverService->withBuilder($closure)->new(),
        ]);
    }


    /**
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     */
    public function discoverdungeonpopular(Dungeon $dungeon, DiscoverServiceInterface $discoverService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.dungeon.category', [
            'category'      => 'popular',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.popular'), __($dungeon->name)),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.popular',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService->withBuilder($closure)->popularByDungeon($dungeon),
        ]);
    }

    /**
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     */
    public function discoverdungeonthisweek(Dungeon $dungeon, DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.dungeon.category', [
            'category'      => 'thisweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.this_week_affixes'), __($dungeon->name)),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.thisweek',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService->withBuilder($closure)->popularByDungeonAndAffixGroup(
                $dungeon,
                $seasonService->getCurrentSeason()->getCurrentAffixGroup()
            ),
            'affixgroup'    => $seasonService->getCurrentSeason()->getCurrentAffixGroup(),
        ]);
    }

    /**
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     * @throws Exception
     */
    public function discoverdungeonnextweek(Dungeon $dungeon, DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.dungeon.category', [
            'category'      => 'nextweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.next_week_affixes'), __($dungeon->name)),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.nextweek',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService->withBuilder($closure)->popularByDungeonAndAffixGroup(
                $dungeon,
                $seasonService->getCurrentSeason()->getNextAffixGroup()
            ),
            'affixgroup'    => $seasonService->getCurrentSeason()->getNextAffixGroup(),
        ]);
    }

    /**
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     */
    public function discoverdungeonnew(Dungeon $dungeon, DiscoverServiceInterface $discoverService)
    {
        $closure = function (Builder $builder)
        {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.dungeon.category', [
            'category'      => 'new',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.new'), __($dungeon->name)),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.new',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService->withBuilder($closure)->newByDungeon($dungeon),
        ]);
    }
}
