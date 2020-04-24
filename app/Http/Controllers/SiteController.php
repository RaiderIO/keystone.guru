<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Service\Season\SeasonService;
use Illuminate\Http\Request;

class SiteController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function home(Request $request)
    {
        return redirect('/', 301);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function credits(Request $request)
    {
        return view('misc.credits');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function about(Request $request)
    {
        return view('misc.about');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function privacy(Request $request)
    {
        return view('legal.privacy');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function terms(Request $request)
    {
        return view('legal.terms');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function cookies(Request $request)
    {
        return view('legal.cookies');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function changelog(Request $request)
    {
        return view('misc.changelog', ['releases' => Release::orderBy('created_at', 'DESC')->paginate(5)]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function mapping(Request $request)
    {
        return view('misc.mapping');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function timetest(Request $request)
    {
        return view('misc.timetest');
    }

    /**
     * @param Request $request
     * @param SeasonService $seasonService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function affixes(Request $request, SeasonService $seasonService)
    {
        return view('misc.affixes', ['seasonService' => $seasonService, 'offset' => (int)$request->get('offset', 0)]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function looptest(Request $request)
    {
        return view('misc.looptest');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function status(Request $request)
    {
        return view('misc.status');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
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
}
