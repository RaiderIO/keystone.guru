<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Timewalking\TimewalkingEvent;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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
     * @param ExpansionService $expansionService
     * @return RedirectResponse
     */
    public function discover(ExpansionService $expansionService)
    {
        return redirect()->route('dungeonroutes.expansion', ['expansion' => $expansionService->getCurrentExpansion()]);
    }

    /**
     * @param Expansion $expansion
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverExpansion(
        Expansion                        $expansion,
        DiscoverServiceInterface         $discoverService,
        SeasonServiceInterface           $seasonService
    )
    {
        $this->authorize('view', $expansion);

        // Redirect to the current expansion
        if (!$expansion->active) {
            return redirect()->route('dungeonroutes');
        }

        $closure = function (Builder $builder) {
            $builder->limit(config('keystoneguru.discover.limits.overview'));
        };

        if ($expansion->hasTimewalkingEvent()) {
            $currentAffixGroup = $expansion->currentseason->affixgroups->first();
            $nextAffixGroup    = $expansion->currentseason->affixgroups->count() > 1 ?
                $expansion->currentseason->affixgroups->get(1) : null;
        } else {
            $currentAffixGroup = $seasonService->getCurrentSeason()->getCurrentAffixGroup();
            $nextAffixGroup    = $seasonService->getCurrentSeason()->getNextAffixGroup();
        }

        $discoverService = $discoverService->withExpansion($expansion);

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'   => 'dungeonroutes.discover',
            'gridDungeons'  => $expansion->dungeons()->active()->get(),
            'expansion'     => $expansion,
            'dungeonroutes' => [
                'thisweek' => $discoverService->popularGroupedByDungeonByAffixGroup($currentAffixGroup),
                'nextweek' => $discoverService->popularGroupedByDungeonByAffixGroup($nextAffixGroup),
                'new'      => $discoverService->withBuilder($closure)->new(),
                'popular'  => $discoverService->popularGroupedByDungeon(),
            ],
        ]);
    }

    /**
     * @param Expansion $expansion
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverdungeon(Expansion $expansion, Dungeon $dungeon, DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $closure = function (Builder $builder) {
            $builder->limit(config('keystoneguru.discover.limits.overview'));
        };

        return view('dungeonroute.discover.dungeon.overview', [
            'expansion'     => $expansion,
            'dungeon'       => $dungeon,
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon',
            'dungeonroutes' => [
                'thisweek' => $discoverService->withBuilder($closure)->popularByDungeonAndAffixGroup($dungeon, $seasonService->getCurrentSeason()->getCurrentAffixGroup()),
                'nextweek' => $discoverService->withBuilder($closure)->popularByDungeonAndAffixGroup($dungeon, $seasonService->getCurrentSeason()->getNextAffixGroup()),
                'new'      => $discoverService->withBuilder($closure)->newByDungeon($dungeon),
                'popular'  => $discoverService->withBuilder($closure)->popularByDungeon($dungeon),
            ],
        ]);
    }

    /**
     * @param Expansion $expansion
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverpopular(Expansion $expansion, DiscoverServiceInterface $discoverService)
    {
        $this->authorize('view', $expansion);

        $closure = function (Builder $builder) {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.category', [
            'expansion'     => $expansion,
            'category'      => 'popular',
            'title'         => __('controller.dungeonroutediscover.popular'),
            'breadcrumbs'   => 'dungeonroutes.popular',
            'dungeonroutes' => $discoverService->withBuilder($closure)->popular(),
        ]);
    }

    /**
     * @param Expansion $expansion
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverthisweek(Expansion $expansion, DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $this->authorize('view', $expansion);

        $closure = function (Builder $builder) {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.category', [
            'expansion'     => $expansion,
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
     * @param Expansion $expansion
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discovernextweek(Expansion $expansion, DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $this->authorize('view', $expansion);

        $closure = function (Builder $builder) {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.category', [
            'expansion'     => $expansion,
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
     * @param Expansion $expansion
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discovernew(Expansion $expansion, DiscoverServiceInterface $discoverService)
    {
        $this->authorize('view', $expansion);

        $closure = function (Builder $builder) {
            $builder->limit(config('keystoneguru.discover.limits.category'));
        };

        return view('dungeonroute.discover.category', [
            'expansion'     => $expansion,
            'category'      => 'new',
            'title'         => __('controller.dungeonroutediscover.new'),
            'breadcrumbs'   => 'dungeonroutes.new',
            'dungeonroutes' => $discoverService->withBuilder($closure)->new(),
        ]);
    }


    /**
     * @param Expansion $expansion
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverdungeonpopular(Expansion $expansion, Dungeon $dungeon, DiscoverServiceInterface $discoverService)
    {
        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $closure = function (Builder $builder) {
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
     * @param Expansion $expansion
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverdungeonthisweek(Expansion $expansion, Dungeon $dungeon, DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $closure = function (Builder $builder) {
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
     * @param Expansion $expansion
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverdungeonnextweek(Expansion $expansion, Dungeon $dungeon, DiscoverServiceInterface $discoverService, SeasonService $seasonService)
    {
        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $closure = function (Builder $builder) {
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
     * @param Expansion $expansion
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverdungeonnew(Expansion $expansion, Dungeon $dungeon, DiscoverServiceInterface $discoverService)
    {
        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $closure = function (Builder $builder) {
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
