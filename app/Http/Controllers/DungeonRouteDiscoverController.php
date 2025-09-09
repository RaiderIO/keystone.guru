<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
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
    public function search(
        SeasonServiceInterface    $seasonService,
        ExpansionServiceInterface $expansionService,
    ): View {
        $currentSeason = $seasonService->getCurrentSeason();
        $nextSeason    = $seasonService->getNextSeasonOfExpansion();

        return view('dungeonroute.discover.search', [
            'currentSeasonKeyLevelMin' => $currentSeason?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'),
            'currentSeasonKeyLevelMax' => $currentSeason?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'),
            'nextSeasonKeyLevelMin'    => $nextSeason?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'),
            'nextSeasonKeyLevelMax'    => $nextSeason?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'),
        ]);
    }

    public function discover(ExpansionService $expansionService): RedirectResponse
    {
        return redirect()->route('dungeonroutes.expansion', [
            'expansion' => $expansionService->getCurrentExpansion(GameServerRegion::getUserOrDefaultRegion()),
        ]);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     *
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverSeason(
        Expansion                $expansion,
        string                   $seasonIndex,
        DiscoverServiceInterface $discoverService
    ) {
        $season = Season::where('expansion_id', $expansion->id)->where('index', $seasonIndex)->first();

        // Redirect to the current expansion
        if ($season === null) {
            return redirect()->route('dungeonroutes');
        }

        $this->authorize('view', $expansion);
        $this->authorize('view', $season);

        $discoverService = $discoverService
            ->withExpansion($expansion)
            ->withSeason($season);

        // Redirect to the current expansion
        if (!$expansion->active) {
            return redirect()->route('dungeonroutes');
        }

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentAffixGroup = $season->getCurrentAffixGroupInRegion($userRegion);
        $nextAffixGroup    = $season->getNextAffixGroupInRegion($userRegion);

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'       => 'dungeonroutes.season',
            'breadcrumbsParams' => [
                $expansion,
                $season,
            ],
            'gridDungeons'      => $season->dungeons()->active()->get(),
            'expansion'         => $expansion,
            'season'            => $season,
            'dungeonroutes'     => [
                'thisweek' => $currentAffixGroup === null ? collect() : $discoverService->popularGroupedByDungeonByAffixGroup($currentAffixGroup),
                'nextweek' => $nextAffixGroup === null ? collect() : $discoverService->popularGroupedByDungeonByAffixGroup($nextAffixGroup),
                'new'      => $discoverService->new(),
                'popular'  => $discoverService->popularGroupedByDungeon(),
            ],
        ]);
    }

    /**
     * @return Factory|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonPopular(
        Expansion                $expansion,
        string                   $seasonIndex,
        DiscoverServiceInterface $discoverService
    ) {
        $season = Season::where('expansion_id', $expansion->id)->where('index', $seasonIndex)->first();

        // Redirect to the current expansion
        if ($season === null) {
            return redirect()->route('dungeonroutes');
        }

        $this->authorize('view', $expansion);
        $this->authorize('view', $season);

        return view('dungeonroute.discover.season.category', [
            'breadcrumbs'       => 'dungeonroutes.season.popular',
            'breadcrumbsParams' => [
                $expansion,
                $season,
            ],
            'expansion'         => $expansion,
            'category'          => 'popular',
            'title'             => sprintf(__('controller.dungeonroutediscover.season.popular'), __($season->name)),
            'season'            => $season,
            'dungeonroutes'     => $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularBySeason($season),
        ]);
    }

    /**
     * @return Factory|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonThisWeek(
        Expansion                 $expansion,
        string                    $seasonIndex,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        SeasonServiceInterface $seasonService
    ) {
        $season = Season::where('expansion_id', $expansion->id)->where('index', $seasonIndex)->first();

        // Redirect to the current expansion
        if ($season === null) {
            return redirect()->route('dungeonroutes');
        }

        $this->authorize('view', $expansion);
        $this->authorize('view', $season);

        $affixGroup = $expansionService->getCurrentAffixGroup($expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.season.category', [
            'breadcrumbs'       => 'dungeonroutes.season.thisweek',
            'breadcrumbsParams' => [
                $expansion,
                $season,
            ],
            'expansion'         => $expansion,
            'category'          => 'thisweek',
            'title'             => sprintf(__('controller.dungeonroutediscover.season.this_week_affixes'), __($season->name)),
            'season'            => $season,
            'dungeonroutes'     => $affixGroup === null ? collect() : $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularBySeasonAndAffixGroup($season, $affixGroup),
            'affixgroup'        => $affixGroup,
        ]);
    }

    /**
     * @param Dungeon $dungeon
     * @return Factory|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonNextWeek(
        Expansion                 $expansion,
        string                    $seasonIndex,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService
    ) {
        $season = Season::where('expansion_id', $expansion->id)->where('index', $seasonIndex)->first();

        // Redirect to the current expansion
        if ($season === null) {
            return redirect()->route('dungeonroutes');
        }

        $this->authorize('view', $expansion);
        $this->authorize('view', $season);

        $affixGroup = $expansionService->getNextAffixGroup($expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.season.category', [
            'breadcrumbs'       => 'dungeonroutes.season.nextweek',
            'breadcrumbsParams' => [
                $expansion,
                $season,
            ],
            'expansion'         => $expansion,
            'category'          => 'nextweek',
            'title'             => sprintf(__('controller.dungeonroutediscover.season.next_week_affixes'), __($season->name)),
            'season'            => $season,
            'dungeonroutes'     => $affixGroup === null ? collect() : $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularBySeasonAndAffixGroup($season, $affixGroup),
            'affixgroup'        => $affixGroup,
        ]);
    }

    /**
     * @return Factory|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonNew(
        Expansion                $expansion,
        string                   $seasonIndex,
        DiscoverServiceInterface $discoverService
    ) {
        $season = Season::where('expansion_id', $expansion->id)->where('index', $seasonIndex)->first();

        // Redirect to the current expansion
        if ($season === null) {
            return redirect()->route('dungeonroutes');
        }

        $this->authorize('view', $expansion);
        $this->authorize('view', $season);

        return view('dungeonroute.discover.season.category', [
            'breadcrumbs'       => 'dungeonroutes.season.new',
            'breadcrumbsParams' => [
                $expansion,
                $season,
            ],
            'expansion'         => $expansion,
            'category'          => 'new',
            'title'             => sprintf(__('controller.dungeonroutediscover.season.new'), __($season->name)),
            'season'            => $season,
            'dungeonroutes'     => $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->newBySeason($season),
        ]);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverExpansion(
        Expansion                 $expansion,
        ExpansionServiceInterface $expansionService,
        DiscoverServiceInterface $discoverService
    ) {
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
            'breadcrumbs'       => 'dungeonroutes.expansion',
            'breadcrumbsParams' => [$expansion],
            'gridDungeons'      => $expansion->dungeonsAndRaids()->active()->get(),
            'expansion'         => $expansion,
            'dungeonroutes'     => [
                'thisweek' => $currentAffixGroup === null ? collect() : $discoverService->popularGroupedByDungeonByAffixGroup($currentAffixGroup),
                'nextweek' => $nextAffixGroup === null ? collect() : $discoverService->popularGroupedByDungeonByAffixGroup($nextAffixGroup),
                'new'      => $discoverService->new(),
                'popular'  => $discoverService->popularGroupedByDungeon(),
            ],
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverdungeon(
        Expansion                 $expansion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        SeasonServiceInterface $seasonService
    ): View {
        $expansion = $this->applyCorrectedExpansion($expansion, $dungeon, $discoverService, $seasonService);

        $this->authorize('view', $expansion);
        $this->authorize('view', $dungeon);

        $discoverService = $discoverService
            ->withExpansion($expansion)
            ->withLimit(config('keystoneguru.discover.limits.overview'));

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentSeason = $seasonService->getCurrentSeason();

        if ($currentSeason->hasDungeon($dungeon)) {
            $currentAffixGroup = $currentSeason->getCurrentAffixGroupInRegion($userRegion);
            $nextAffixGroup    = $currentSeason->getNextAffixGroupInRegion($userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $currentAffixGroup = $expansionService->getCurrentAffixGroup($expansion, $userRegion);
            $nextAffixGroup    = $expansionService->getNextAffixGroup($expansion, $userRegion);
        }

        return view('dungeonroute.discover.dungeon.overview', [
            'breadcrumbs'       => 'dungeonroutes.discoverdungeon',
            'expansion'         => $expansion,
            'dungeon'           => $dungeon,
            'currentAffixGroup' => $currentAffixGroup,
            'nextAffixGroup'    => $nextAffixGroup,
            'dungeonroutes'     => [
                'thisweek' => $currentAffixGroup === null ? collect() : $discoverService->popularByDungeonAndAffixGroup($dungeon, $currentAffixGroup),
                'nextweek' => $nextAffixGroup === null ? collect() : $discoverService->popularByDungeonAndAffixGroup($dungeon, $nextAffixGroup),
                'new'      => $discoverService->newByDungeon($dungeon),
                'popular'  => $discoverService->popularByDungeon($dungeon),
            ],
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverpopular(Expansion $expansion, DiscoverServiceInterface $discoverService): View
    {
        $this->authorize('view', $expansion);

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.popular',
            'expansion'     => $expansion,
            'category'      => 'popular',
            'title'         => __('controller.dungeonroutediscover.popular'),
            'dungeonroutes' => $discoverService->withExpansion($expansion)->withLimit(config('keystoneguru.discover.limits.category'))->popular(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverthisweek(
        Expansion                 $expansion,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService
    ): View {
        $this->authorize('view', $expansion);

        $affixGroup = $expansionService->getCurrentAffixGroup($expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.thisweek',
            'expansion'     => $expansion,
            'category'      => 'thisweek',
            'title'         => __('controller.dungeonroutediscover.this_week_affixes'),
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByAffixGroup($affixGroup),
            'affixgroup'    => $affixGroup,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discovernextweek(
        Expansion                 $expansion,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService
    ): View {
        $this->authorize('view', $expansion);

        $affixGroup = $expansionService->getNextAffixGroup($expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.nextweek',
            'expansion'     => $expansion,
            'category'      => 'nextweek',
            'title'         => __('controller.dungeonroutediscover.next_week_affixes'),
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByAffixGroup($affixGroup),
            'affixgroup'    => $affixGroup,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discovernew(Expansion $expansion, DiscoverServiceInterface $discoverService): View
    {
        $this->authorize('view', $expansion);

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.new',
            'expansion'     => $expansion,
            'category'      => 'new',
            'title'         => __('controller.dungeonroutediscover.new'),
            'dungeonroutes' => $discoverService
                ->withExpansion($expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->new(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverdungeonpopular(
        Expansion                $expansion,
        Dungeon                  $dungeon,
        DiscoverServiceInterface $discoverService
    ): View {
        $this->authorize('view', $dungeon->expansion);
        $this->authorize('view', $dungeon);

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.popular',
            'expansion'     => $dungeon->expansion,
            'category'      => 'popular',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.popular'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->withExpansion($dungeon->expansion)
                ->popularByDungeon($dungeon),
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverdungeonthisweek(
        Expansion                 $expansion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        SeasonServiceInterface $seasonService
    ): View {
        $expansion = $this->applyCorrectedExpansion($expansion, $dungeon, $discoverService, $seasonService);

        $this->authorize('view', $dungeon->expansion);
        $this->authorize('view', $dungeon);

        $userRegion    = GameServerRegion::getUserOrDefaultRegion();
        $currentSeason = $seasonService->getCurrentSeason(null, $userRegion);

        if ($currentSeason->hasDungeon($dungeon)) {
            $currentAffixGroup = $currentSeason->getCurrentAffixGroupInRegion($userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $currentAffixGroup = $expansionService->getCurrentAffixGroup($expansion, $userRegion);
        }

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.thisweek',
            'expansion'     => $dungeon->expansion,
            'category'      => 'thisweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.this_week_affixes'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $currentAffixGroup === null ? collect() : $discoverService
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeonAndAffixGroup($dungeon, $currentAffixGroup),
            'affixgroup'    => $currentAffixGroup,
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverdungeonnextweek(
        Expansion                 $expansion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        SeasonServiceInterface $seasonService
    ): View {
        $expansion = $this->applyCorrectedExpansion($expansion, $dungeon, $discoverService, $seasonService);

        $this->authorize('view', $dungeon->expansion);
        $this->authorize('view', $dungeon);

        $userRegion    = GameServerRegion::getUserOrDefaultRegion();
        $currentSeason = $seasonService->getCurrentSeason(null, $userRegion);

        if ($currentSeason->hasDungeon($dungeon)) {
            $nextAffixGroup = $currentSeason->getNextAffixGroupInRegion($userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $nextAffixGroup = $expansionService->getNextAffixGroup($expansion, $userRegion);
        }

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.nextweek',
            'expansion'     => $dungeon->expansion,
            'category'      => 'nextweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.next_week_affixes'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $nextAffixGroup === null ? collect() : $discoverService
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeonAndAffixGroup($dungeon, $nextAffixGroup),
            'affixgroup'    => $nextAffixGroup,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverdungeonnew(
        Expansion                $expansion,
        Dungeon                  $dungeon,
        DiscoverServiceInterface $discoverService
    ): View {
        $this->authorize('view', $dungeon->expansion);
        $this->authorize('view', $dungeon);

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.new',
            'expansion'     => $dungeon->expansion,
            'category'      => 'new',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.new'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService
                ->withExpansion($dungeon->expansion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->newByDungeon($dungeon),
        ]);
    }

    /**
     * It can happen that your current dungeon (say Halls of Valor) is part of the current expansion's current season (DF S1)
     * If so - we need to change the expansion for said dungeon from Legion to (in this case) Dragonflight. Otherwise,
     * it will find affixes for the timewalking season and not for the current season, leading to incorrect affixes.
     *
     * This function will correct this mistake and apply the correct expansion + season.
     */
    private function applyCorrectedExpansion(
        Expansion                $originalExpansion,
        Dungeon                  $dungeon,
        DiscoverServiceInterface $discoverService,
        SeasonServiceInterface   $seasonService
    ): Expansion {
        $result = $originalExpansion;

        // First - check if this dungeon is part of the current expansion's season, regardless of the season it originated from
        $currentSeason = $seasonService->getCurrentSeason();
        if ($currentSeason->hasDungeon($dungeon)) {
            // If it does, change the expansion to the current expansion so that the correct affixes are found
            $result = $currentSeason->expansion;
            $discoverService->withSeason($currentSeason);
        }

        return $result;
    }
}
