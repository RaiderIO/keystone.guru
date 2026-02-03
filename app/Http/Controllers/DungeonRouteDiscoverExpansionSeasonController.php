<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DungeonRouteDiscoverExpansionSeasonController extends Controller
{
    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     *
     * @throws AuthorizationException
     * @throws Exception
     */
    public function discoverSeason(
        Expansion                $expansion,
        string                   $seasonIndex,

        DiscoverServiceInterface $discoverService,
    ) {
        $gameVersion = GameVersion::firstWhere('expansion_id', $expansion->id) ?? GameVersion::getDefaultGameVersion();

        $season = Season::where('expansion_id', $expansion->id)
            ->where('index', $seasonIndex)->first();

        Gate::authorize('view', $gameVersion);
        Gate::authorize('view', $expansion);
        Gate::authorize('view', $season);

        $discoverService = $discoverService
            ->withExpansion($expansion)
            ->withSeason($season);

        $userRegion = GameServerRegion::getUserOrDefaultRegion();

        $currentAffixGroup = $season->getCurrentAffixGroupInRegion($userRegion);
        $nextAffixGroup    = $season->getNextAffixGroupInRegion($userRegion);

        return view('dungeonroute.discover.discover', [
            'breadcrumbs'       => 'dungeonroutes.expansion.season',
            'breadcrumbsParams' => [
                $expansion,
                $season,
            ],
            'gridDungeons'  => $season->dungeons()->active()->get(),
            'gameVersion'   => $gameVersion,
            'expansion'     => $expansion,
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
}
