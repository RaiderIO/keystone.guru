<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Features\DungeonRouteListRework;
use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class DungeonRouteDiscoverController extends Controller
{
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
        GameVersion               $gameVersion,
        string                    $seasonIndex,
        DiscoverServiceInterface  $discoverService,
        ThumbnailServiceInterface $thumbnailService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', [Season::class, $season]);

        $discoverService = $discoverService
            ->withGameVersion($gameVersion)
            ->withSeason($season);

        $newDungeonRoutes     = $discoverService->new();
        $popularDungeonRoutes = $discoverService->popularGroupedByDungeon();
        $thumbnailService->dungeonRoutesDisplayed($newDungeonRoutes->merge($popularDungeonRoutes->flatten(1)));

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
                'new'     => $newDungeonRoutes,
                'popular' => $popularDungeonRoutes,
            ],
        ]);
    }

    /**
     * @return Factory|View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonPopular(
        GameVersion               $gameVersion,
        string                    $seasonIndex,
        DiscoverServiceInterface  $discoverService,
        ThumbnailServiceInterface $thumbnailService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', [Season::class, $season]);

        $dungeonRoutes = $discoverService
            ->withGameVersion($gameVersion)
            ->withLimit(config('keystoneguru.discover.limits.category'))
            ->popularBySeason($season);
        $thumbnailService->dungeonRoutesDisplayed($dungeonRoutes);

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
            'dungeonroutes' => $dungeonRoutes,
        ]);
    }

    /**
     * @return Factory|View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverSeasonNew(
        GameVersion               $gameVersion,
        string                    $seasonIndex,
        DiscoverServiceInterface  $discoverService,
        ThumbnailServiceInterface $thumbnailService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', [Season::class, $season]);

        $dungeonRoutes = $discoverService
            ->withGameVersion($gameVersion)
            ->withLimit(config('keystoneguru.discover.limits.category'))
            ->newBySeason($season);
        $thumbnailService->dungeonRoutesDisplayed($dungeonRoutes);

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
            'dungeonroutes' => $dungeonRoutes,
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
        ThumbnailServiceInterface   $thumbnailService,
    ) {
        Gate::authorize('view', $expansion);

        $discoverService = $discoverService->withExpansion($expansion);

        $gameVersion = $gameVersionService->getGameVersion(Auth::user());

        $newDungeonRoutes     = $discoverService->new();
        $popularDungeonRoutes = $discoverService->popularGroupedByDungeon();
        $thumbnailService->dungeonRoutesDisplayed($newDungeonRoutes->merge($popularDungeonRoutes->flatten(1)));

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'       => 'dungeonroutes.expansion',
            'breadcrumbsParams' => [$expansion],
            'gridDungeons'      => $expansion->dungeonsAndRaids()->active()->get()->sortBy('id')->values(),
            'gameVersion'       => $gameVersion,
            'expansion'         => $expansion,
            'dungeonroutes'     => [
                'new'     => $newDungeonRoutes,
                'popular' => $popularDungeonRoutes,
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
        ThumbnailServiceInterface        $thumbnailService,
    ): View {
        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $dungeon);

        $discoverService = $discoverService
            ->withGameVersion($gameVersion)
            ->excludeTeam(Team::getRaiderIOTeam());

        $currentSeason          = $seasonService->getCurrentSeason();
        $dungeonInCurrentSeason = $currentSeason->hasDungeon($dungeon);

        // The popular ordering is season-scoped when the dungeon belongs to the current season.
        if ($dungeonInCurrentSeason) {
            $discoverService = $discoverService->withSeason($currentSeason);
        }

        // Weekly routes keep their WeeklyRoute DTOs so the reworked overview can read the archetype
        // (WeeklyRoute->type); the flag-off panel consumes the plain route collection below.
        $dungeonWeeklyRoutes = $dungeonRouteRepository->getWeeklyRoutes($dungeon)[$dungeon->key] ?? collect();
        $weeklyDungeonRoutes = $dungeonWeeklyRoutes->map(fn(WeeklyRoute $weeklyRoute) => $weeklyRoute->dungeonRoute);
        // Weekly slots without a route stay null; the legacy panel expects them
        $displayedWeeklyDungeonRoutes = $weeklyDungeonRoutes->filter()->values();

        $dungeonService->setDungeonContext($dungeon, Auth::user());
        $gameVersionDungeons = $dungeonService->getDungeonsForGameVersion($gameVersion);

        if (Feature::active(DungeonRouteListRework::class)) {
            // The reworked overview is the paginated popular leaderboard: the standalone popular/new
            // category pages fold into this page (they redirect here).
            $paginator = $discoverService->popularByDungeonPaginated(
                $dungeon,
                (int)config('keystoneguru.discover.limits.leaderboard'),
            );
            // The weekly hero band only renders on the first page
            $thumbnailService->dungeonRoutesDisplayed(
                ($paginator->onFirstPage() ? $displayedWeeklyDungeonRoutes : collect())->merge($paginator->items()),
            );

            return view('dungeonroute.discover.dungeon.overview', [
                'breadcrumbs'  => 'dungeonroutes.discoverdungeon',
                'gameVersion'  => $gameVersion,
                'dungeon'      => $dungeon,
                'weeklyRoutes' => $dungeonWeeklyRoutes
                    ->filter(fn(WeeklyRoute $weeklyRoute) => $weeklyRoute->dungeonRoute !== null)
                    ->values(),
                'paginator'           => $paginator,
                'gameVersionDungeons' => $gameVersionDungeons,
            ]);
        }

        // Flag off: the legacy multi-panel overview, unchanged.
        $discoverService = $discoverService->withLimit(config('keystoneguru.discover.limits.overview'));

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        if ($dungeonInCurrentSeason) {
            $currentAffixGroup = $seasonAffixGroupService->getCurrentAffixGroupInRegion($currentSeason, $userRegion);
        } else {
            $currentAffixGroup = $expansionService->getCurrentAffixGroup($gameVersion->expansion, $userRegion);
        }

        $newDungeonRoutes     = $discoverService->newByDungeon($dungeon);
        $popularDungeonRoutes = $discoverService->popularByDungeon($dungeon);
        $thumbnailService->dungeonRoutesDisplayed($displayedWeeklyDungeonRoutes->merge($newDungeonRoutes)->merge($popularDungeonRoutes));

        return view('dungeonroute.discover.dungeon.overview', [
            'breadcrumbs'       => 'dungeonroutes.discoverdungeon',
            'gameVersion'       => $gameVersion,
            'dungeon'           => $dungeon,
            'currentAffixGroup' => $currentAffixGroup,
            'weeklyRoutes'      => $dungeonWeeklyRoutes
                ->filter(fn(WeeklyRoute $weeklyRoute) => $weeklyRoute->dungeonRoute !== null)
                ->values(),
            'dungeonroutes' => [
                'weekly_route' => $weeklyDungeonRoutes,
                'new'          => $newDungeonRoutes,
                'popular'      => $popularDungeonRoutes,
            ],
            'gameVersionDungeons' => $gameVersionDungeons,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverPopular(
        GameVersion               $gameVersion,
        DiscoverServiceInterface  $discoverService,
        ThumbnailServiceInterface $thumbnailService,
    ): View {
        Gate::authorize('view', $gameVersion);

        $dungeonRoutes = $discoverService
            ->withGameVersion($gameVersion)
            ->excludeTeam(Team::getRaiderIOTeam())
            ->withLimit(config('keystoneguru.discover.limits.category'))
            ->popular();
        $thumbnailService->dungeonRoutesDisplayed($dungeonRoutes);

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.popular',
            'gameVersion'   => $gameVersion,
            'category'      => 'popular',
            'title'         => __('controller.dungeonroutediscover.popular'),
            'dungeonroutes' => $dungeonRoutes,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverNew(
        GameVersion               $gameVersion,
        DiscoverServiceInterface  $discoverService,
        ThumbnailServiceInterface $thumbnailService,
    ): View {
        Gate::authorize('view', $gameVersion);

        $dungeonRoutes = $discoverService
            ->withGameVersion($gameVersion)
            ->withLimit(config('keystoneguru.discover.limits.category'))
            ->new();
        $thumbnailService->dungeonRoutesDisplayed($dungeonRoutes);

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.new',
            'gameVersion'   => $gameVersion,
            'category'      => 'new',
            'title'         => __('controller.dungeonroutediscover.new'),
            'dungeonroutes' => $dungeonRoutes,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverDungeonPopular(
        GameVersion               $gameVersion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
        DungeonServiceInterface   $dungeonService,
        ThumbnailServiceInterface $thumbnailService,
    ): View|RedirectResponse {
        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $dungeon);

        // The reworked overview folds popular into the base dungeon page; keep the URL crawlable.
        if (Feature::active(DungeonRouteListRework::class)) {
            return redirect()->route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ], 301);
        }

        $dungeonService->setDungeonContext($dungeon, Auth::user());

        $dungeonRoutes = $discoverService
            ->withGameVersion($gameVersion)
            ->withLimit(config('keystoneguru.discover.limits.category'))
            ->popularByDungeon($dungeon);
        $thumbnailService->dungeonRoutesDisplayed($dungeonRoutes);

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'         => 'dungeonroutes.discoverdungeon.popular',
            'gameVersion'         => $gameVersion,
            'category'            => 'popular',
            'title'               => sprintf(__('controller.dungeonroutediscover.dungeon.popular'), __($dungeon->name)),
            'dungeon'             => $dungeon,
            'dungeonroutes'       => $dungeonRoutes,
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverDungeonNew(
        GameVersion               $gameVersion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
        DungeonServiceInterface   $dungeonService,
        ThumbnailServiceInterface $thumbnailService,
    ): View|RedirectResponse {
        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $dungeon);

        // The reworked overview folds the per-dungeon categories into the base page; keep the URL crawlable.
        if (Feature::active(DungeonRouteListRework::class)) {
            return redirect()->route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ], 301);
        }

        $dungeonService->setDungeonContext($dungeon, Auth::user());

        $dungeonRoutes = $discoverService
            ->withGameVersion($gameVersion)
            ->withLimit(config('keystoneguru.discover.limits.category'))
            ->newByDungeon($dungeon);
        $thumbnailService->dungeonRoutesDisplayed($dungeonRoutes);

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'         => 'dungeonroutes.discoverdungeon.new',
            'gameVersion'         => $gameVersion,
            'category'            => 'new',
            'title'               => sprintf(__('controller.dungeonroutediscover.dungeon.new'), __($dungeon->name)),
            'dungeon'             => $dungeon,
            'dungeonroutes'       => $dungeonRoutes,
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion),
        ]);
    }
}
