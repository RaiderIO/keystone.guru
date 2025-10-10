<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DungeonRouteDiscoverController extends Controller
{
    /**
     * @return Factory|View
     */
    public function search(
        SeasonServiceInterface $seasonService,
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
        GameVersion              $gameVersion,
        string                   $seasonIndex,
        DiscoverServiceInterface $discoverService,
    ) {
        // Redirect to the default game version (retail, which DOES have seasons and is active)
        if (!$gameVersion->has_seasons) {
            return redirect()->route('dungeonroutes');
        }

        $season = Season::where('expansion_id', $gameVersion->expansion_id)->where('index', $seasonIndex)->first();

        $this->authorize('view', $gameVersion);
        $this->authorize('view', $season);

        $discoverService = $discoverService
            ->withGameVersion($gameVersion)
            ->withSeason($season);

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentAffixGroup = $season->getCurrentAffixGroupInRegion($userRegion);
        $nextAffixGroup    = $season->getNextAffixGroupInRegion($userRegion);

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'       => 'dungeonroutes.season',
            'breadcrumbsParams' => [
                $gameVersion,
                $season,
            ],
            'gridDungeons'  => $season->dungeons()->active()->get(),
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
     * @return Factory|RedirectResponse
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

        $this->authorize('view', $gameVersion);
        $this->authorize('view', $season);

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
     * @return Factory|RedirectResponse
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

        $this->authorize('view', $gameVersion);
        $this->authorize('view', $season);

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
     * @param  Dungeon                  $dungeon
     * @return Factory|RedirectResponse
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

        $this->authorize('view', $gameVersion);
        $this->authorize('view', $season);

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
     * @return Factory|RedirectResponse
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

        $this->authorize('view', $gameVersion);
        $this->authorize('view', $season);

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
        $this->authorize('view', $expansion);

        $discoverService = $discoverService->withExpansion($expansion);

        $gameVersion = $gameVersionService->getGameVersion(Auth::user());

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'       => 'dungeonroutes.expansion',
            'breadcrumbsParams' => [$expansion],
            'gridDungeons'      => $expansion->dungeonsAndRaids()->active()->get(),
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

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function discoverGameVersion(
        GameVersion               $gameVersion,
        ExpansionServiceInterface $expansionService,
        DiscoverServiceInterface  $discoverService,
    ) {
        $this->authorize('view', $gameVersion);

        $discoverService = $discoverService->withGameVersion($gameVersion);

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'       => 'dungeonroutes.gameVersion',
            'breadcrumbsParams' => [$gameVersion],
            'gridDungeons'      => $gameVersion->expansion->dungeonsAndRaids()->active()->get(),
            'gameVersion'       => $gameVersion,
            'dungeonroutes'     => [
                'thisweek' => collect(),
                'nextweek' => collect(),
                'new'      => $discoverService->new(),
                'popular'  => $discoverService->popularGroupedByDungeon(),
            ],
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverDungeon(
        GameVersion               $gameVersion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        SeasonServiceInterface    $seasonService,
    ): View {
        $this->authorize('view', $gameVersion);
        $this->authorize('view', $dungeon);

        $discoverService = $discoverService
            ->withGameVersion($gameVersion)
            ->withLimit(config('keystoneguru.discover.limits.overview'));

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentSeason = $seasonService->getCurrentSeason();

        if ($currentSeason->hasDungeon($dungeon)) {
            $currentAffixGroup = $currentSeason->getCurrentAffixGroupInRegion($userRegion);
            $nextAffixGroup    = $currentSeason->getNextAffixGroupInRegion($userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $currentAffixGroup = $expansionService->getCurrentAffixGroup($gameVersion->expansion, $userRegion);
            $nextAffixGroup    = $expansionService->getNextAffixGroup($gameVersion->expansion, $userRegion);
        }

        return view('dungeonroute.discover.dungeon.overview', [
            'breadcrumbs'       => 'dungeonroutes.discoverdungeon',
            'gameVersion'       => $gameVersion,
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
    public function discoverPopular(GameVersion $gameVersion, DiscoverServiceInterface $discoverService): View
    {
        $this->authorize('view', $gameVersion);

        return view('dungeonroute.discover.category', [
            'breadcrumbs'   => 'dungeonroutes.popular',
            'gameVersion'   => $gameVersion,
            'category'      => 'popular',
            'title'         => __('controller.dungeonroutediscover.popular'),
            'dungeonroutes' => $discoverService->withGameVersion($gameVersion)->withLimit(config('keystoneguru.discover.limits.category'))->popular(),
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
        $this->authorize('view', $gameVersion);

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
        $this->authorize('view', $gameVersion);

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
        $this->authorize('view', $gameVersion);

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
    ): View {
        $this->authorize('view', $gameVersion);
        $this->authorize('view', $dungeon);

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
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverDungeonThisWeek(
        GameVersion               $gameVersion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        SeasonServiceInterface    $seasonService,
    ): View {
        $this->authorize('view', $gameVersion);
        $this->authorize('view', $dungeon);

        $userRegion    = GameServerRegion::getUserOrDefaultRegion();
        $currentSeason = $seasonService->getCurrentSeason(null, $userRegion);

        if ($currentSeason->hasDungeon($dungeon)) {
            $currentAffixGroup = $currentSeason->getCurrentAffixGroupInRegion($userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $currentAffixGroup = $expansionService->getCurrentAffixGroup($gameVersion->expansion, $userRegion);
        }

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.thisweek',
            'gameVersion'   => $gameVersion,
            'category'      => 'thisweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.this_week_affixes'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $currentAffixGroup === null ? collect() : $discoverService
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeonAndAffixGroup($dungeon, $currentAffixGroup),
            'affixgroup' => $currentAffixGroup,
        ]);
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverDungeonNextWeek(
        GameVersion               $gameVersion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
        ExpansionServiceInterface $expansionService,
        SeasonServiceInterface    $seasonService,
    ): View {
        $this->authorize('view', $gameVersion);
        $this->authorize('view', $dungeon);

        $userRegion    = GameServerRegion::getUserOrDefaultRegion();
        $currentSeason = $seasonService->getCurrentSeason($gameVersion->expansion, $userRegion);

        if ($currentSeason->hasDungeon($dungeon)) {
            $nextAffixGroup = $currentSeason->getNextAffixGroupInRegion($userRegion);

            $discoverService = $discoverService->withSeason($currentSeason);
        } else {
            $nextAffixGroup = $expansionService->getNextAffixGroup($gameVersion->expansion, $userRegion);
        }

        return view('dungeonroute.discover.dungeon.category', [
            'breadcrumbs'   => 'dungeonroutes.discoverdungeon.nextweek',
            'gameVersion'   => $gameVersion,
            'category'      => 'nextweek',
            'title'         => sprintf(__('controller.dungeonroutediscover.dungeon.next_week_affixes'), __($dungeon->name)),
            'dungeon'       => $dungeon,
            'dungeonroutes' => $nextAffixGroup === null ? collect() : $discoverService
                ->withLimit(config('keystoneguru.discover.limits.category'))
                ->popularByDungeonAndAffixGroup($dungeon, $nextAffixGroup),
            'affixgroup' => $nextAffixGroup,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function discoverDungeonNew(
        GameVersion              $gameVersion,
        Dungeon                  $dungeon,
        DiscoverServiceInterface $discoverService,
    ): View {
        $this->authorize('view', $gameVersion);
        $this->authorize('view', $dungeon);

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
        ]);
    }
}
