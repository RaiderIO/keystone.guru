<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Jobs\RegenerateCombatLogRoute;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Session;

class AdminToolsCombatLogController extends Controller
{
    public function combatLogRouteEnemyFailures(
        Request                    $request,
        MapContextServiceInterface $mapContextService,
    ): RedirectResponse|View {
        $dungeon        = Dungeon::getUserOrDefaultDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($request->has('dungeon_id') && (int)$request->input('dungeon_id') !== $dungeon->id) {
            return redirect()->route('admin.tools.combatlog.route.enemy_failures.view', [
                'dungeon_id' => $dungeon->id,
            ]);
        }

        abort_if($mappingVersion === null, 404);

        $floor      = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();
        $mapContext = $mapContextService->createMapContextDungeonExplore($dungeon, $mappingVersion, User::getCurrentUserMapFacadeStyle());

        return view('admin.tools.combatlog.combatlogroute_enemy_failures', compact('dungeon', 'floor', 'mappingVersion', 'mapContext'));
    }

    public function combatlogregenerate(): View
    {
        return view('admin.tools.combatlog.regenerate');
    }

    public function combatlogregeneratesubmit(Request $request): View
    {
        set_time_limit(3600);

        $dungeonId = (int)$request->get('dungeon_id');

        $count = 0;

        // Cannot use joins since the other table lives in a different database
        DungeonRoute::query()
            ->when($dungeonId !== -1, static fn(Builder $builder) => $builder->where('dungeon_id', $dungeonId))
            ->chunkById(200, function (Collection $dungeonRoutes) use (&$count) {
                $dungeonRoutes = $dungeonRoutes->keyBy('id');
                /** @var Collection<ChallengeModeRun> $challengeModes */
                $challengeModes = ChallengeModeRun::whereIn('dungeon_route_id', $dungeonRoutes->pluck('id'))
                    ->get();

                foreach ($challengeModes as $challengeMode) {
                    RegenerateCombatLogRoute::dispatch(
                        $dungeonRoutes->get($challengeMode->dungeon_route_id)->id,
                    );
                    $count++;
                }
            });

        Session::flash('status', __('controller.admintools.flash.combatlog_route_regenerate_result', [
            'count' => $count,
        ]));

        return view('admin.tools.combatlog.regenerate');
    }
}
