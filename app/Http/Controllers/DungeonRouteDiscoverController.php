<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Expansion\ExpansionServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
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
     * @param ExpansionServiceInterface $expansionService
     * @param DiscoverServiceInterface $discoverService
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     * @throws AuthorizationException
     */
    public function discoverExpansion(
        Expansion                 $expansion,
        ExpansionServiceInterface $expansionService,
        DiscoverServiceInterface  $discoverService
    )
    {
        $this->authorize('view', $expansion);

        $discoverService = $discoverService->withExpansion($expansion);

        // Redirect to the current expansion
        if (!$expansion->active) {
            return redirect()->route('dungeonroutes');
        }

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentAffixGroup = $expansionService->getCurrentAffixGroup($expansion, $userRegion);
        $nextAffixGroup    = $expansionService->getNextAffixGroup($expansion, $userRegion);

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'   => 'dungeonroutes.discover',
            'gridDungeons'  => $expansion->dungeons()->active()->get(),
            'expansion'     => $expansion,
            'dungeonroutes' => [
                'thisweek' => $currentAffixGroup === null ? collect() : $discoverService->popularGroupedByDungeonByAffixGroup($currentAffixGroup),
                'nextweek' => $nextAffixGroup === null ? collect() : $discoverService->popularGroupedByDungeonByAffixGroup($nextAffixGroup),
                'new'      => $discoverService->new(),
                'popular'  => $discoverService->popularGroupedByDungeon(),
            ],
        ]);
    }

    /**
     * @param Expansion $expansion
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param ExpansionServiceInterface $expansionService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverdungeon(Expansion $expansion, Dungeon $dungeon, DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService)
    {
        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $discoverService = $discoverService->withExpansion($expansion)->withLimit(config('keystoneguru.discover.limits.overview'));

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentAffixGroup = $expansionService->getCurrentAffixGroup($expansion, $userRegion);
        $nextAffixGroup    = $expansionService->getNextAffixGroup($expansion, $userRegion);

        return view('dungeonroute.discover.dungeon.overview', [
            'expansion'     => $expansion,
            'dungeon'       => $dungeon,
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon',
            'dungeonroutes' => [
                'thisweek' => $currentAffixGroup === null ? collect() : $discoverService->popularByDungeonAndAffixGroup($dungeon, $currentAffixGroup),
                'nextweek' => $nextAffixGroup === null ? collect() : $discoverService->popularByDungeonAndAffixGroup($dungeon, $nextAffixGroup),
                'new'      => $discoverService->newByDungeon($dungeon),
                'popular'  => $discoverService->popularByDungeon($dungeon),
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

        return view('dungeonroute.discover.category', [
            'expansion'     => $expansion,
            'category'      => 'popular',
            'title'         => __('controller.dungeonroutediscover.popular'),
            'breadcrumbs'   => 'dungeonroutes.popular',
            'dungeonroutes' => $discoverService->withExpansion($expansion)->withLimit(config('keystoneguru.discover.limits.category'))->popular(),
        ]);
    }

    /**
     * @param Expansion $expansion
     * @param DiscoverServiceInterface $discoverService
     * @param ExpansionServiceInterface $expansionService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverthisweek(Expansion $expansion, DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService)
    {
        $this->authorize('view', $expansion);

        $affixGroup = $expansionService->getCurrentAffixGroup($expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.category', [
            'expansion'     => $expansion,
            'category'      => 'thisweek',
            'title'         => __('controller.dungeonroutediscover.this_week_affixes'),
            'breadcrumbs'   => 'dungeonroutes.thisweek',
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByAffixGroup($affixGroup),
            'affixgroup'    => $affixGroup,
        ]);
    }

    /**
     * @param Expansion $expansion
     * @param DiscoverServiceInterface $discoverService
     * @param ExpansionServiceInterface $expansionService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discovernextweek(Expansion $expansion, DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService)
    {
        $this->authorize('view', $expansion);

        $affixGroup = $expansionService->getNextAffixGroup($expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.category', [
            'expansion'     => $expansion,
            'category'      => 'nextweek',
            'title'         => __('controller.dungeonroutediscover.next_week_affixes'),
            'breadcrumbs'   => 'dungeonroutes.nextweek',
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByAffixGroup($affixGroup),
            'affixgroup'    => $affixGroup,
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

        return view('dungeonroute.discover.category', [
            'expansion'     => $expansion,
            'category'      => 'new',
            'title'         => __('controller.dungeonroutediscover.new'),
            'breadcrumbs'   => 'dungeonroutes.new',
            'dungeonroutes' => $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->new(),
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

        return view('dungeonroute.discover.dungeon.category', [
            'expansion'     => $expansion,
            'category'      => 'popular',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.popular'), __($dungeon->name)),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.popular',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeon($dungeon),
        ]);
    }

    /**
     * @param Expansion $expansion
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param ExpansionServiceInterface $expansionService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverdungeonthisweek(Expansion $expansion, Dungeon $dungeon, DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService)
    {
        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $affixGroup = $expansionService->getCurrentAffixGroup($expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.dungeon.category', [
            'expansion'     => $expansion,
            'category'      => 'thisweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.this_week_affixes'), __($dungeon->name)),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.thisweek',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeonAndAffixGroup($dungeon, $affixGroup),
            'affixgroup'    => $affixGroup,
        ]);
    }

    /**
     * @param Expansion $expansion
     * @param Dungeon $dungeon
     * @param DiscoverServiceInterface $discoverService
     * @param ExpansionServiceInterface $expansionService
     * @return Factory
     * @throws AuthorizationException
     */
    public function discoverdungeonnextweek(Expansion $expansion, Dungeon $dungeon, DiscoverServiceInterface $discoverService, ExpansionServiceInterface $expansionService)
    {
        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $affixGroup = $expansionService->getNextAffixGroup($expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.dungeon.category', [
            'expansion'     => $expansion,
            'category'      => 'nextweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.next_week_affixes'), __($dungeon->name)),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.nextweek',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeonAndAffixGroup($dungeon, $affixGroup),
            'affixgroup'    => $affixGroup,
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

        return view('dungeonroute.discover.dungeon.category', [
            'expansion'     => $expansion,
            'category'      => 'new',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.new'), __($dungeon->name)),
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.new',
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->newByDungeon($dungeon),
        ]);
    }
}
