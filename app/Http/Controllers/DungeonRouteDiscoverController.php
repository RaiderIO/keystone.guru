<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Contracts\View\Factory;
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
        $limit = config('keystoneguru.discover.limits.overview');

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'   => 'dungeonroutes.discover',
            'dungeonroutes' => [
                'thisweek' => $discoverService->popularGroupedByDungeonByAffixGroup($seasonService->getCurrentSeason()->getCurrentAffixGroup()),
                'nextweek' => $discoverService->popularGroupedByDungeonByAffixGroup($seasonService->getCurrentSeason()->getNextAffixGroup()),
                'new'      => $discoverService->new($limit),
                // 'popular'  => $discoverService->popular(),
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
        $limit = config('keystoneguru.discover.limits.overview');

        return view('dungeonroute.discover.dungeon.overview', [
            'dungeon'       => $dungeon,
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon',
            'dungeonroutes' => [
                'thisweek' => $discoverService->popularByDungeonAndAffixGroup($dungeon, $seasonService->getCurrentSeason()->getCurrentAffixGroup(), $limit),
                'nextweek' => $discoverService->popularByDungeonAndAffixGroup($dungeon, $seasonService->getCurrentSeason()->getNextAffixGroup(), $limit),
                'new'      => $discoverService->newByDungeon($dungeon, $limit),
                // 'popular'  => $discoverService->popularByDungeon($dungeon, $limit),
            ]
        ]);
    }

    /**
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     */
    public function discoverpopular(DiscoverServiceInterface $discoverService)
    {
        return view('dungeonroute.discover.category', [
            'title'         => __('Popular routes'),
            'breadcrumbs'   => 'dungeonroutes.popular',
            'dungeonroutes' => $discoverService->popular(
                config('keystoneguru.discover.limits.category')
            ),
        ]);
    }

    /**
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     */
    public function discoverthisweek(DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        return view('dungeonroute.discover.category', [
            'title'         => __('This week\'s affixes'),
            'breadcrumbs'   => 'dungeonroutes.thisweek',
            'dungeonroutes' => $discoverService->popularByAffixGroup(
                $seasonService->getCurrentSeason()->getCurrentAffixGroup(),
                config('keystoneguru.discover.limits.category')
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
        return view('dungeonroute.discover.category', [
            'title'         => __('Next week\'s affixes'),
            'breadcrumbs'   => 'dungeonroutes.nextweek',
            'dungeonroutes' => $discoverService->popularByAffixGroup(
                $seasonService->getCurrentSeason()->getNextAffixGroup(),
                config('keystoneguru.discover.limits.category')
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
        return view('dungeonroute.discover.category', [
            'title'         => __('New routes'),
            'breadcrumbs'   => 'dungeonroutes.new',
            'dungeonroutes' => $discoverService->new(
                config('keystoneguru.discover.limits.category')
            ),
        ]);
    }


    /**
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     */
    public function discoverdungeonpopular(Dungeon $dungeon, DiscoverServiceInterface $discoverService)
    {
        return view('dungeonroute.discover.dungeon.category', [
            'title'         => sprintf(__('%s popular routes'), $dungeon->name),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.popular',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService->popularByDungeon(
                $dungeon,
                config('keystoneguru.discover.limits.category')
            ),
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
        return view('dungeonroute.discover.dungeon.category', [
            'title'         => sprintf(__('%s this week'), $dungeon->name),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.thisweek',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService->popularByDungeonAndAffixGroup(
                $dungeon,
                $seasonService->getCurrentSeason()->getCurrentAffixGroup(),
                config('keystoneguru.discover.limits.category')
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
        return view('dungeonroute.discover.dungeon.category', [
            'title'         => sprintf(__('%s next week'), $dungeon->name),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.nextweek',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService->popularByDungeonAndAffixGroup(
                $dungeon,
                $seasonService->getCurrentSeason()->getNextAffixGroup(),
                config('keystoneguru.discover.limits.category')
            ),
            'affixgroup'    => $seasonService->getCurrentSeason()->getCurrentAffixGroup(),
        ]);
    }

    /**
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     */
    public function discoverdungeonnew(Dungeon $dungeon, DiscoverServiceInterface $discoverService)
    {
        return view('dungeonroute.discover.dungeon.category', [
            'title'         => sprintf(__('%s new routes'), $dungeon->name),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.new',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService->newByDungeon(
                $dungeon,
                config('keystoneguru.discover.limits.category')
            ),
        ]);
    }
}
