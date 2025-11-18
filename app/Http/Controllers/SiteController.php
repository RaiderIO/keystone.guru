<?php

namespace App\Http\Controllers;

use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Logic\Utils\Stopwatch;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Release;
use App\Models\Season;
use App\Models\User;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
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
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;
use Predis\Response\Status;
use Teapot\StatusCode;

class SiteController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return Application|Factory|View
     */
    public function test(): View
    {
        return view('misc.test');
    }

    /**
     * Show the application dashboard.
     *
     * @return Application|Factory|View
     */
    public function index(CoverageServiceInterface $coverageService, SeasonService $seasonService): View
    {
        if (Auth::check()) {
            $season = null;
            if (isset($_COOKIE['dungeonroute_coverage_season_id'])) {
                $season = Season::find($_COOKIE['dungeonroute_coverage_season_id']);
            }

            $season ??= $seasonService->getCurrentSeason();

            /** @var User $user */
            $user = Auth::user();

            return view('profile.overview', [
                'dungeonRoutes' => $coverageService->getForUser($user, $season),
            ]);
        } else {
            return view('home');
        }
    }

    /**
     * @return string
     */
    public function benchmark(
        Request                                    $request,
        CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService,
    ) {
        $validated = json_decode(file_get_contents(app()->basePath('tmp/combatlog.json')), true);

        Stopwatch::clear();

        Stopwatch::start('SiteController::benchmark');
        $result = $combatLogRouteDungeonRouteService->correctCombatLogRoute(
            CombatLogRouteRequestModel::createFromArray($validated),
        );
        Stopwatch::stop('SiteController::benchmark');

        return "hello";
    }

    /**
     * @return RedirectResponse|Redirector
     */
    public function home(Request $request): RedirectResponse
    {
        return redirect('/', 301);
    }

    /**
     * @return Factory|View
     */
    public function credits(Request $request): View
    {
        return view('misc.credits');
    }

    /**
     * @return Factory|View
     */
    public function about(Request $request): View
    {
        return view('misc.about');
    }

    /**
     * @return Factory|View
     */
    public function privacy(Request $request): View
    {
        return view('legal.privacy');
    }

    /**
     * @return Factory|View
     */
    public function terms(Request $request): View
    {
        return view('legal.terms');
    }

    /**
     * @return Factory|View
     */
    public function cookies(Request $request): View
    {
        return view('legal.cookies');
    }

    /**
     * @return Application|Factory|View|RedirectResponse
     */
    public function changelog(Request $request)
    {
        $releases = Release::where('released', 1)
            ->orderBy('created_at', 'DESC')->paginate(5);
        if ($releases->isEmpty()) {
            return redirect()->route('misc.changelog');
        } else {
            return view('misc.changelog', ['releases' => $releases]);
        }
    }

    /**
     * @return Factory|View
     */
    public function health(Request $request): View
    {
        return view('misc.health');
    }

    /**
     * @return Factory|View
     */
    public function mapping(Request $request): View
    {
        return view('misc.mapping');
    }

    /**
     * @return Factory|View
     */
    public function timetest(Request $request): View
    {
        return view('misc.timetest');
    }

    /**
     * @return Factory|View
     *
     * @throws Exception
     */
    public function affixes(
        Request                          $request,
        DiscoverServiceInterface         $discoverService,
        SeasonService                    $seasonService,
        ExpansionService                 $expansionService,
        TimewalkingEventServiceInterface $timewalkingEventService,
    ): View {
        $currentExpansion = $expansionService->getCurrentExpansion(GameServerRegion::getUserOrDefaultRegion());

        $minOffset = -50;
        $maxOffset = 10;
        $offset    = (int)$request->get('offset', 0);
        $offset    = max(min($offset, $maxOffset), $minOffset);

        return view('misc.affixes', [
            'timewalkingEventService' => $timewalkingEventService,
            'expansion'               => $currentExpansion,
            'gameVersion'             => GameVersion::getDefaultGameVersion(),
            'seasonService'           => $seasonService,
            'offset'                  => $offset,
            'showPrevious'            => $offset > $minOffset,
            'showNext'                => $offset < $maxOffset,
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
     * @return Response
     */
    public function status(Request $request): Response
    {
        $checks = [
            'database' => [
                'ok'    => false,
                'error' => null,
            ],
            'redis' => [
                'ok'    => false,
                'error' => null,
            ],
            'disk' => [
                'ok'    => false,
                'error' => null,
            ],
        ];

        // Database check: simple query
        try {
            DB::connection()->getPdo(); // ensure PDO established
            DB::select('SELECT 1');     // trivial round trip
            $checks['database']['ok'] = true;
        } catch (\Throwable $e) {
            $checks['database']['error'] = $e->getMessage();
        }

        // Redis check: PING
        try {
            /** @var Status $pong */
            $pong = Redis::connection()->client()->ping();

            // Some clients return "PONG" or true
            $checks['redis']['ok'] = $pong->getPayload() === 'PONG';
            if (!$checks['redis']['ok']) {
                $checks['redis']['error'] = 'Unexpected PING response';
            }
        } catch (\Throwable $e) {
            $checks['redis']['error'] = $e->getMessage();
        }

        // Check if the disk is writable
        $checks['disk']['ok'] = is_writable(storage_path());
        if (!$checks['disk']['ok']) {
            $checks['disk']['error'] = 'Storage path is not writable';
        }

        $success = true;
        foreach ($checks as $check) {
            $success = $success && $check['ok'];
        }

        return response()
            ->view('misc.status', ['checks' => $checks])
            ->setStatusCode($success ? StatusCode::OK : StatusCode::SERVICE_UNAVAILABLE);
    }

    /**
     * @return Application|Redirector|RedirectResponse
     */
    public function dungeonroutes(Request $request): RedirectResponse
    {
        return redirect(route('dungeonroutes'), 301);
    }

    public function phpinfo(Request $request): void
    {
        phpinfo();
    }

    /**
     * @return Application|Factory|View
     */
    public function embed(Request $request, DungeonRoute $dungeonRoute): View
    {
        return view('misc.embed', [
            'model'      => $dungeonRoute,
            'parameters' => $request->all(),
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function embedExplore(
        Request     $request,
        GameVersion $gameVersion,
        Dungeon     $dungeon,
        string      $floorIndex = '1',
    ): View {
        return view('misc.embedexplore', [
            'gameVersion' => $gameVersion,
            'model'       => $dungeon,
            'floorIndex'  => $floorIndex,
            'parameters'  => $request->all(),
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function embedHeatmap(
        Request     $request,
        GameVersion $gameVersion,
        Dungeon     $dungeon,
        string      $floorIndex = '1',
    ): View {
        return view('misc.embedheatmap', [
            'gameVersion' => $gameVersion,
            'model'       => $dungeon,
            'floorIndex'  => $floorIndex,
            'parameters'  => $request->all(),
        ]);
    }
}
