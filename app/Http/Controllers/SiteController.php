<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\Release;
use App\Models\Season;
use App\Service\DungeonRoute\CoverageServiceInterface;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Season\SeasonService;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiteController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return Application|Factory|View
     */
    public function test()
    {
        return view('misc.test');
    }

    /**
     * Show the application dashboard.
     *
     * @return Application|Factory|View
     */
    public function index(CoverageServiceInterface $coverageService, SeasonService $seasonService)
    {
        if (Auth::check()) {
            $season = null;
            if (isset($_COOKIE['dungeonroute_coverage_season_id'])) {
                $season = Season::find($_COOKIE['dungeonroute_coverage_season_id']);
            }
            $season = $season ?? $seasonService->getCurrentSeason();

            return view('profile.overview', [
                'dungeonRoutes' => $coverageService->getForUser(Auth::user(), $season),
            ]);
        } else {
            return view('home');
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Redirector
     */
    public function home(Request $request)
    {
        return redirect('/', 301);
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function credits(Request $request)
    {
        return view('misc.credits');
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function about(Request $request)
    {
        return view('misc.about');
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function privacy(Request $request)
    {
        return view('legal.privacy');
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function terms(Request $request)
    {
        return view('legal.terms');
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function cookies(Request $request)
    {
        return view('legal.cookies');
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse
     */
    public function changelog(Request $request)
    {
        $releases = Release::orderBy('created_at', 'DESC')->paginate(5);
        if ($releases->isEmpty()) {
            return redirect()->route('misc.changelog');
        } else {
            return view('misc.changelog', ['releases' => $releases]);
        }
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function health(Request $request)
    {
        return view('misc.health');
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function mapping(Request $request)
    {
        return view('misc.mapping');
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function timetest(Request $request)
    {
        return view('misc.timetest');
    }

    /**
     * @param Request $request
     * @param DiscoverServiceInterface $discoverService
     * @param SeasonService $seasonService
     * @param ExpansionService $expansionService
     * @param TimewalkingEventServiceInterface $timewalkingEventService
     * @return Factory|View
     * @throws Exception
     */
    public function affixes(
        Request                          $request,
        DiscoverServiceInterface         $discoverService,
        SeasonService                    $seasonService,
        ExpansionService                 $expansionService,
        TimewalkingEventServiceInterface $timewalkingEventService
    )
    {
        $currentExpansion = $expansionService->getCurrentExpansion();

        return view('misc.affixes', [
            'timewalkingEventService' => $timewalkingEventService,
            'expansion'               => $currentExpansion,
            'seasonService'           => $seasonService,
            'offset'                  => max(min((int)$request->get('offset', 0), 10), -20),
            'dungeonroutes'           => [
                'thisweek' => $discoverService
                    ->withLimit(config('keystoneguru.discover.limits.affix_overview'))
                    ->popularByAffixGroup($seasonService->getCurrentSeason($currentExpansion)->getCurrentAffixGroup()),
                'nextweek' => $discoverService
                    ->withLimit(config('keystoneguru.discover.limits.affix_overview'))
                    ->popularByAffixGroup($seasonService->getCurrentSeason($currentExpansion)->getNextAffixGroup()),
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return Factory|View
     */
    public function status(Request $request)
    {
        return view('misc.status');
    }

    /**
     * @param Request $request
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function dungeonroutes(Request $request)
    {
        return redirect(route('dungeonroutes'), 301);
    }

    /**
     * @param Request $request
     */
    public function phpinfo(Request $request)
    {
        phpinfo();
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function embed(Request $request, DungeonRoute $dungeonroute)
    {
        return view('misc.embed', ['model' => $dungeonroute]);
    }
}
