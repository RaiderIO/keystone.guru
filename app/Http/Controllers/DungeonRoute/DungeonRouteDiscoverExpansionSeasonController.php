<?php

namespace App\Http\Controllers\DungeonRoute;

use App\Http\Controllers\Controller;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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
                'new'     => $discoverService->new(),
                'popular' => $discoverService->popularGroupedByDungeon(),
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
