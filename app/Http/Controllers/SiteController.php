<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\Release;
use App\Service\Expansion\ExpansionService;
use App\Service\Season\SeasonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

class SiteController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @param ExpansionService $expansionService
     *
     * @return Application|Factory|View
     */
    public function index(ExpansionService $expansionService)
    {
        return view('home', ['expansionService' => $expansionService]);
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
     * @return Factory|View
     */
    public function changelog(Request $request)
    {
        return view('misc.changelog', ['releases' => Release::orderBy('created_at', 'DESC')->paginate(5)]);
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
     * @param SeasonService $seasonService
     * @return Factory|View
     */
    public function affixes(Request $request, SeasonService $seasonService)
    {
        return view('misc.affixes', ['seasonService' => $seasonService, 'offset' => (int)$request->get('offset', 0)]);
    }

    /**
     * @param Request $request
     * @param ExpansionService $expansionService
     * @return Factory|View
     */
    public function demo(Request $request, ExpansionService $expansionService)
    {
        return view('misc.demo', ['expansionService' => $expansionService]);
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
