<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\Release;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Season\SeasonService;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
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
    public function index()
    {
        if (Auth::check()) {
            return view('profile.overview');
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
     * @return Factory|View
     */
    public function affixes(
        Request                          $request,
        DiscoverServiceInterface         $discoverService,
        SeasonService                    $seasonService,
        ExpansionService                 $expansionService,
        TimewalkingEventServiceInterface $timewalkingEventService
    )
    {
        $closure = function (Builder $builder) {
            $builder->limit(config('keystoneguru.discover.limits.affix_overview'));
        };

        return view('misc.affixes', [
            'timewalkingEventService' => $timewalkingEventService,
            'expansion'               => $expansionService->getCurrentExpansion(),
            'seasonService'           => $seasonService,
            'offset'                  => (int)$request->get('offset', 0),
            'dungeonroutes'           => [
                'thisweek' => $discoverService->withBuilder($closure)->popularByAffixGroup($seasonService->getCurrentSeason()->getCurrentAffixGroup()),
                'nextweek' => $discoverService->withBuilder($closure)->popularByAffixGroup($seasonService->getCurrentSeason()->getNextAffixGroup()),
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
