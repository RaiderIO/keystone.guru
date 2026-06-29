<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Features\DungeonOverview;
use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class DungeonRouteDiscoverController extends Controller
{
    /**
     * Relations a dungeon route card needs eager-loaded to render without lazy-loading violations.
     * Mirrors what the DiscoverService loads for its own route collections.
     *
     * @var array<int, string>
     */
    private const ROUTE_CARD_RELATIONS = [
        'author',
        'affixes',
        'ratings',
        'mappingVersion',
        'thumbnails',
        'dungeon',
        'season.expansion',
    ];

    /**
     * @return View
     */
    public function search(
        SeasonServiceInterface $seasonService,
    ): View {
        $currentSeason = $seasonService->getCurrentSeason();
        $nextSeason    = $seasonService->getNextSeasonOfExpansion();

        return view('dungeonroute.discover.search', [
            'currentSeasonKeyLevelMin' => $currentSeason?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'), // @phpstan-ignore nullsafe.neverNull
            'currentSeasonKeyLevelMax' => $currentSeason?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'), // @phpstan-ignore nullsafe.neverNull
            'nextSeasonKeyLevelMin'    => $nextSeason?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'), // @phpstan-ignore nullsafe.neverNull
            'nextSeasonKeyLevelMax'    => $nextSeason?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'), // @phpstan-ignore nullsafe.neverNull
        ]);
    }

    public function discover(): RedirectResponse
    {
        return redirect()->route('dungeonroutes.gameVersion', [
            'gameVersion' => GameVersion::getDefaultGameVersion(),
        ]);
    }

    public function discoverCurrentGameVersion(
        GameVersionServiceInterface $gameVersionService,
        SeasonServiceInterface      $seasonService,
    ): RedirectResponse {
        $gameVersion = $gameVersionService->getGameVersion(Auth::user());
        if ($gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes.season', [
                'gameVersion' => $gameVersion,
                'season'      => $seasonService->getCurrentSeason($gameVersion->expansion)->index,
            ]);
        } else {
            return redirect()->route('dungeonroutes.gameVersion', [
                'gameVersion' => $gameVersion,
            ]);
        }
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     *
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverSeason(
        GameVersion                      $gameVersion,
        string                           $seasonIndex,
        DiscoverServiceInterface         $discoverService,
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $season);

        $discoverService = $discoverService
            ->withGameVersion($gameVersion)
            ->withSeason($season);

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentAffixGroup = $seasonAffixGroupService->getCurrentAffixGroupInRegion($season, $userRegion);
        $nextAffixGroup    = $seasonAffixGroupService->getNextAffixGroupInRegion($season, $userRegion);

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'       => 'dungeonroutes.season',
            'breadcrumbsParams' => [
                $gameVersion,
                $season,
            ],
            'gridDungeons'  => $season->dungeons()->active()->get()->sortBy('id')->values(),
            'gameVersion'   => $gameVersion,
            'season'        => $season,
            'dungeonroutes' => [
                'thisweek' => $currentAffixGroup === null ? collect() : $discoverService->popularGroupedByDungeonByAffixGroup($currentAffixGroup),
                'nextweek' => $nextAffixGroup === null ? collect() : $discoverService->popularGroupedByDungeonByAffixGroup($nextAffixGroup),
                'new'      => $discoverService->new(),
                'popular'  => $discoverService->popularGroupedByDungeon(),
            ],
        ]);
    }

    /**
     * @return Factory|View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonPopular(
        GameVersion              $gameVersion,
        string                   $seasonIndex,
        DiscoverServiceInterface $discoverService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $season);

        return view('dungeonroute.discover.season.category', [
            'breadcrumbs'       => 'dungeonroutes.season.popular',
            'breadcrumbsParams' => [
                $gameVersion,
                $season,
            ],
            'gameVersion'   => $gameVersion,
            'category'      => 'popular',
            'title'         => sprintf(__('controller.dungeonroutediscover.season.popular'), __($season->name)),
            'season'        => $season,
            'dungeonroutes' => $discoverService
                ->withGameVersion($gameVersion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularBySeason($season),
        ]);
    }

    /**
     * @return Factory|View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonThisWeek(
        GameVersion               $gameVersion,
        string                    $seasonIndex,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        SeasonServiceInterface    $seasonService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $season);

        $affixGroup = $expansionService->getCurrentAffixGroup($gameVersion->expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.season.category', [
            'breadcrumbs'       => 'dungeonroutes.season.thisweek',
            'breadcrumbsParams' => [
                $gameVersion,
                $season,
            ],
            'gameVersion'   => $gameVersion,
            'category'      => 'thisweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.season.this_week_affixes'), __($season->name)),
            'season'        => $season,
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withGameVersion($gameVersion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularBySeasonAndAffixGroup($season, $affixGroup),
            'affixgroup' => $affixGroup,
        ]);
    }

    /**
     * @return Factory|View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonNextWeek(
        GameVersion               $gameVersion,
        string                    $seasonIndex,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $season);

        $affixGroup = $expansionService->getNextAffixGroup($gameVersion->expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.season.category', [
            'breadcrumbs'       => 'dungeonroutes.season.nextweek',
            'breadcrumbsParams' => [
                $gameVersion,
                $season,
            ],
            'gameVersion'   => $gameVersion,
            'category'      => 'nextweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.season.next_week_affixes'), __($season->name)),
            'season'        => $season,
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withGameVersion($gameVersion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularBySeasonAndAffixGroup($season, $affixGroup),
            'affixgroup' => $affixGroup,
        ]);
    }

    /**
     * @return Factory|View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonNew(
        GameVersion              $gameVersion,
        string                   $seasonIndex,
        DiscoverServiceInterface $discoverService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $season);

        return view('dungeonroute.discover.season.category', [
            'breadcrumbs'       => 'dungeonroutes.season.new',
            'breadcrumbsParams' => [
                $gameVersion,
                $season,
            ],
            'gameVersion'   => $gameVersion,
            'category'      => 'new',
            'title'         => sprintf(__('controller.dungeonroutediscover.season.new'), __($season->name)),
            'season'        => $season,
            'dungeonroutes' => $discoverService
                ->withGameVersion($gameVersion)
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
        Expansion                   $expansion,
        DiscoverServiceInterface    $discoverService,
        GameVersionServiceInterface $gameVersionService,
    ) {
        Gate::authorize('view', $expansion);

        $discoverService = $discoverService->withExpansion($expansion);

        $gameVersion = $gameVersionService->getGameVersion(Auth::user());

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'       => 'dungeonroutes.expansion',
            'breadcrumbsParams' => [$expansion],
            'gridDungeons'      => $expansion->dungeonsAndRaids()->active()->get()->sortBy('id')->values(),
            'gameVersion'       => $gameVersion,
            'expansion'         => $expansion,
            'dungeonroutes'     => [
                'thisweek' => collect(),
                'nextweek' => collect(),
                'new'      => $discoverService->new(),
                'popular'  => $discoverService->popularGroupedByDungeon(),
            ],
        ]);
    }

    public function discoverGameVersion(
        GameVersion             $gameVersion,
        DungeonServiceInterface $dungeonService,
    ) : RedirectResponse {
        Gate::authorize('view', $gameVersion);

        $contextDungeon = $dungeonService->getDungeonContext(Auth::user());

        return redirect()->route('dungeonroutes.discoverdungeon', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $contextDungeon,
        ]);

//        $discoverService = $discoverService->withGameVersion($gameVersion);
//
//        return view('dungeonroute.discover.discover', [
//            'breadcrumbs'       => 'dungeonroutes.gameVersion',
//            'breadcrumbsParams' => [$gameVersion],
//            'gridDungeons'      => $gameVersion->expansion->dungeonsAndRaids()->active()->get()->sortBy('id')->values(),
//            'gameVersion'       => $gameVersion,
//            'dungeonroutes'     => [
//                'thisweek' => collect(),
//                'nextweek' => collect(),
//                'new'      => $discoverService->new(),
//                'popular'  => $discoverService->popularGroupedByDungeon(),
//            ],
//        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverDungeon(
        GameVersion                      $gameVersion,
        Dungeon                          $dungeon,
        DiscoverServiceInterface         $discoverService,
        ExpansionServiceInterface        $expansionService,
        SeasonServiceInterface           $seasonService,
        DungeonServiceInterface          $dungeonService,
        DungeonRouteRepositoryInterface  $dungeonRouteRepository,
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
    ): View {
        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $dungeon);

        $discoverService = $discoverService
            ->withGameVersion($gameVersion)
            ->excludeTeam(Team::getRaiderIOTeam())
            ->withLimit(config('keystoneguru.discover.limits.overview'));

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentSeason = $seasonService->getCurrentSeason();

        if ($currentSeason->hasDungeon($dungeon)) {
            $currentAffixGroup = $seasonAffixGroupService->getCurrentAffixGroupInRegion($currentSeason, $userRegion);
            $nextAffixGroup    = $seasonAffixGroupService->getNextAffixGroupInRegion($currentSeason, $userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $currentAffixGroup = $expansionService->getCurrentAffixGroup($gameVersion->expansion, $userRegion);
            $nextAffixGroup    = $expansionService->getNextAffixGroup($gameVersion->expansion, $userRegion);
        }

        $weeklyRoutes = $dungeonRouteRepository->getWeeklyRoutes($dungeon);

        $dungeonService->setDungeonContext($dungeon, Auth::user());

        if (Feature::active(DungeonOverview::class)) {
            /** @var Collection<int, WeeklyRoute> $weeklyRoutesForDungeon */
            $weeklyRoutesForDungeon = $weeklyRoutes[$dungeon->key] ?? collect();
            EloquentCollection::make(
                $weeklyRoutesForDungeon
                    ->map(fn(WeeklyRoute $weeklyRoute) => $weeklyRoute->dungeonRoute)
                    ->filter()
                    ->values(),
            )->load(self::ROUTE_CARD_RELATIONS);

            return view('dungeonroute.discover.dungeon.landing', [
                // No breadcrumbs on the landing itself - the trail's last crumb just duplicates the page
                // title. Breadcrumbs reappear (with this dungeon as a clickable parent) on nested pages.
                'breadcrumbs'       => '',
                'gameVersion'       => $gameVersion,
                'dungeon'           => $dungeon,
                'currentAffixGroup' => $currentAffixGroup,
                'weeklyRoutes'      => $weeklyRoutesForDungeon,
                'popularRoutes'     => $discoverService
                    ->withLimit(config('keystoneguru.discover.limits.dungeon_overview_popular'))
                    ->popularByDungeon($dungeon),
                'userRoutes'          => $this->getUserRoutesForDungeon($dungeon),
                'dungeonStats'        => $this->getDungeonStats($dungeon, $gameVersion),
                'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion),
            ]);
        }

        return view('dungeonroute.discover.dungeon.overview', [
            'breadcrumbs'       => 'dungeonroutes.discoverdungeon',
            'gameVersion'       => $gameVersion,
            'dungeon'           => $dungeon,
            'currentAffixGroup' => $currentAffixGroup,
            'nextAffixGroup'    => $nextAffixGroup,
            'dungeonroutes'     => [
                'weekly_route' => ($weeklyRoutes[$dungeon->key] ?? collect())->map(function (WeeklyRoute $weeklyRoute) {
                    return $weeklyRoute->dungeonRoute;
                }),
                'thisweek' => $currentAffixGroup === null ? collect() : $discoverService->popularByDungeonAndAffixGroup($dungeon, $currentAffixGroup),
                'nextweek' => $nextAffixGroup === null ? collect() : $discoverService->popularByDungeonAndAffixGroup($dungeon, $nextAffixGroup),
                'new'      => $discoverService->newByDungeon($dungeon),
                'popular'  => $discoverService->popularByDungeon($dungeon),
            ],
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverPopular(
        GameVersion              $gameVersion,
        DiscoverServiceInterface $discoverService,
    ): View {
        Gate::authorize('view', $gameVersion);

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.popular',
            'gameVersion'   => $gameVersion,
            'category'      => 'popular',
            'title'         => __('controller.dungeonroutediscover.popular'),
            'dungeonroutes' => $discoverService
                ->withGameVersion($gameVersion)
                ->excludeTeam(Team::getRaiderIOTeam())
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popular(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverThisWeek(
        GameVersion               $gameVersion,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
    ): View {
        Gate::authorize('view', $gameVersion);

        $affixGroup = $expansionService->getCurrentAffixGroup($gameVersion->expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.thisweek',
            'gameVersion'   => $gameVersion,
            'category'      => 'thisweek',
            'title'         => __('controller.dungeonroutediscover.this_week_affixes'),
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withGameVersion($gameVersion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByAffixGroup($affixGroup),
            'affixgroup' => $affixGroup,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverNextWeek(
        GameVersion               $gameVersion,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
    ): View {
        Gate::authorize('view', $gameVersion);

        $affixGroup = $expansionService->getNextAffixGroup($gameVersion->expansion, GameServerRegion::getUserOrDefaultRegion());

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.nextweek',
            'gameVersion'   => $gameVersion,
            'category'      => 'nextweek',
            'title'         => __('controller.dungeonroutediscover.next_week_affixes'),
            'dungeonroutes' => $affixGroup === null ? collect() : $discoverService
                ->withGameVersion($gameVersion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByAffixGroup($affixGroup),
            'affixgroup' => $affixGroup,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverNew(GameVersion $gameVersion, DiscoverServiceInterface $discoverService): View
    {
        Gate::authorize('view', $gameVersion);

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.new',
            'gameVersion'   => $gameVersion,
            'category'      => 'new',
            'title'         => __('controller.dungeonroutediscover.new'),
            'dungeonroutes' => $discoverService
                ->withGameVersion($gameVersion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->new(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverDungeonPopular(
        GameVersion              $gameVersion,
        Dungeon                  $dungeon,
        DiscoverServiceInterface $discoverService,
        DungeonServiceInterface  $dungeonService,
    ): View {
        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $dungeon);

        $dungeonService->setDungeonContext($dungeon, Auth::user());

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.popular',
            'gameVersion'   => $gameVersion,
            'category'      => 'popular',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.popular'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->withGameVersion($gameVersion)
                ->popularByDungeon($dungeon),
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion),
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverDungeonThisWeek(
        GameVersion                      $gameVersion,
        Dungeon                          $dungeon,
        DiscoverServiceInterface         $discoverService,
        ExpansionServiceInterface        $expansionService,
        SeasonServiceInterface           $seasonService,
        DungeonServiceInterface          $dungeonService,
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
    ): View|RedirectResponse {
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $dungeon);

        $userRegion    = GameServerRegion::getUserOrDefaultRegion();
        $currentSeason = $seasonService->getCurrentSeason(null, $userRegion);

        if ($currentSeason->hasDungeon($dungeon)) {
            $currentAffixGroup = $seasonAffixGroupService->getCurrentAffixGroupInRegion($currentSeason, $userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $currentAffixGroup = $expansionService->getCurrentAffixGroup($gameVersion->expansion, $userRegion);
        }

        $dungeonService->setDungeonContext($dungeon, Auth::user());

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.thisweek',
            'gameVersion'   => $gameVersion,
            'category'      => 'thisweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.this_week_affixes'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $currentAffixGroup === null ? collect() : $discoverService
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeonAndAffixGroup($dungeon, $currentAffixGroup),
            'affixgroup'          => $currentAffixGroup,
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion),
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverDungeonNextWeek(
        GameVersion                      $gameVersion,
        Dungeon                          $dungeon,
        DiscoverServiceInterface         $discoverService,
        ExpansionServiceInterface        $expansionService,
        SeasonServiceInterface           $seasonService,
        DungeonServiceInterface          $dungeonService,
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
    ): View|RedirectResponse {
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $dungeon);

        $userRegion    = GameServerRegion::getUserOrDefaultRegion();
        $currentSeason = $seasonService->getCurrentSeason($gameVersion->expansion, $userRegion);

        if ($currentSeason->hasDungeon($dungeon)) {
            $nextAffixGroup = $seasonAffixGroupService->getNextAffixGroupInRegion($currentSeason, $userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $nextAffixGroup = $expansionService->getNextAffixGroup($gameVersion->expansion, $userRegion);
        }

        $dungeonService->setDungeonContext($dungeon, Auth::user());

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.nextweek',
            'gameVersion'   => $gameVersion,
            'category'      => 'nextweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.next_week_affixes'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $nextAffixGroup === null ? collect() : $discoverService
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeonAndAffixGroup($dungeon, $nextAffixGroup),
            'affixgroup'          => $nextAffixGroup,
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverDungeonNew(
        GameVersion              $gameVersion,
        Dungeon                  $dungeon,
        DiscoverServiceInterface $discoverService,
        DungeonServiceInterface  $dungeonService,
    ): View {
        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $dungeon);

        $dungeonService->setDungeonContext($dungeon, Auth::user());

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.new',
            'gameVersion'   => $gameVersion,
            'category'      => 'new',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.new'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $discoverService
                ->withGameVersion($gameVersion)
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->newByDungeon($dungeon),
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion),
        ]);
    }

    /**
     * The current user's own (non-demo, non-sandbox) routes for a dungeon, most recently edited first.
     *
     * @return Collection<int, DungeonRoute>
     */
    private function getUserRoutesForDungeon(Dungeon $dungeon): Collection
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return collect();
        }

        return $user->dungeonRoutes()
            ->where('dungeon_id', $dungeon->id)
            ->whereNull('expires_at')
            ->where('demo', false)
            ->with(self::ROUTE_CARD_RELATIONS)
            ->orderByDesc('updated_at')
            ->limit(config('keystoneguru.discover.limits.per_dungeon'))
            ->get();
    }

    /**
     * Cached headline stats for the dungeon overview: compendium counts plus pull/enemy stats for the
     * dungeon's current mapping version.
     *
     * @return array{npc: int, spell: int, pull_count: int, avg_enemies_per_pull: float}
     */
    private function getDungeonStats(Dungeon $dungeon, GameVersion $gameVersion): array
    {
        $mappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);

        return Cache::remember(
            sprintf('dungeon.overview.stats.%d.%d', $dungeon->id, $gameVersion->id),
            now()->addHour(),
            static function () use ($dungeon, $mappingVersion): array {
                $pullCount  = 0;
                $enemyCount = 0;

                if ($mappingVersion !== null) {
                    $pullCount = $dungeon->enemyPacks()
                        ->where('enemy_packs.mapping_version_id', $mappingVersion->id)
                        ->count();

                    $enemyCount = $dungeon->enemies()
                        ->where('enemies.mapping_version_id', $mappingVersion->id)
                        ->where(static function (Builder $query) {
                            $query->whereNull('enemies.seasonal_type')
                                ->orWhere('enemies.seasonal_type', '!=', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER);
                        })
                        ->count();
                }

                return [
                    'npc'                  => $dungeon->npcs()->count(),
                    'spell'                => $dungeon->spells()->count(),
                    'pull_count'           => $pullCount,
                    'avg_enemies_per_pull' => $pullCount > 0 ? round($enemyCount / $pullCount, 1) : 0.0,
                ];
            },
        );
    }
}
