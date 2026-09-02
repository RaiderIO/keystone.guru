<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminToolsCombatLogRegenerateRequest;
use App\Http\Requests\AdminToolsCombatLogRouteEnemyFailuresRequest;
use App\Jobs\RegenerateCombatLogRoute;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Season;
use App\Models\User;
use App\Service\CombatLog\CombatLogRouteEnemyFailureServiceInterface;
use App\Service\Floor\FloorResolutionServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Session;

class AdminToolsCombatLogController extends Controller
{
    public function combatLogRouteEnemyFailures(
        AdminToolsCombatLogRouteEnemyFailuresRequest $request,
        FloorResolutionServiceInterface              $floorResolutionService,
    ): RedirectResponse {
        $dungeon = Dungeon::getUserOrDefaultDungeon();

        if ($request->has('dungeon_id') && (int)$request->input('dungeon_id') !== $dungeon->id) {
            return redirect()->route('admin.tools.combatlog.route.enemy_failures.view', [
                'dungeon_id' => $dungeon->id,
            ]);
        }

        $mappingVersion = $request->getMappingVersion() ?? $dungeon->getCurrentMappingVersion();

        abort_if($mappingVersion === null, 404);
        // A mapping version of another dungeon makes no sense here - and would build the wrong map context below
        abort_if($mappingVersion->dungeon_id !== $dungeon->id, 404);

        $defaultFloor = $floorResolutionService->resolveDefaultFloor($dungeon, $mappingVersion);

        return redirect()->route('admin.tools.combatlog.route.enemy_failures.view.floor', [
            'floorIndex' => $defaultFloor->index,
        ] + $request->validated());
    }

    public function combatLogRouteEnemyFailuresFloor(
        AdminToolsCombatLogRouteEnemyFailuresRequest $request,
        MapContextServiceInterface                   $mapContextService,
        CombatLogRouteEnemyFailureServiceInterface   $combatLogRouteEnemyFailureService,
        FloorResolutionServiceInterface              $floorResolutionService,
        string                                       $floorIndex,
    ): RedirectResponse|View {
        $dungeon = Dungeon::getUserOrDefaultDungeon();

        if ($request->has('dungeon_id') && (int)$request->input('dungeon_id') !== $dungeon->id) {
            return redirect()->route('admin.tools.combatlog.route.enemy_failures.view', [
                'dungeon_id' => $dungeon->id,
            ]);
        }

        $mappingVersion = $request->getMappingVersion() ?? $dungeon->getCurrentMappingVersion();

        abort_if($mappingVersion === null, 404);
        // A mapping version of another dungeon makes no sense here - and would build the wrong map context below
        abort_if($mappingVersion->dungeon_id !== $dungeon->id, 404);

        $resolvedFloor = $floorResolutionService->resolveRequestedFloor($dungeon, $mappingVersion, $floorIndex);

        if (!$resolvedFloor->isCanonical) {
            return redirect()->route('admin.tools.combatlog.route.enemy_failures.view.floor', [
                'floorIndex' => $resolvedFloor->floor->index,
            ] + $request->validated());
        }

        $floor = $resolvedFloor->floor;

        $mapContext = $mapContextService->createMapContextDungeonExplore($dungeon, $mappingVersion, User::getCurrentUserMapFacadeStyle());

        $dungeon->load(['mappingVersions.gameVersion']);
        $mappingVersionFailureCounts = $combatLogRouteEnemyFailureService->getFailureCountsPerMappingVersion($dungeon);
        $npcFailureCounts            = $combatLogRouteEnemyFailureService->getFailureCountsPerNpc($dungeon, $mappingVersion);
        $npcs                        = $this->getEnemyFailureNpcs($mappingVersion, $npcFailureCounts);

        return view('admin.tools.combatlog.combatlogroute_enemy_failures', compact(
            'dungeon',
            'floor',
            'mappingVersion',
            'mapContext',
            'mappingVersionFailureCounts',
            'npcFailureCounts',
            'npcs',
        ));
    }

    /**
     * The npcs to offer in the failure filter: every npc in the mapping version plus every npc that has failures
     * without being in the mapping version at all (the most interesting kind), most failures first.
     *
     * @param  Collection<int, int> $npcFailureCounts npc_id => failure count
     * @return Collection<int, Npc>
     */
    private function getEnemyFailureNpcs(MappingVersion $mappingVersion, Collection $npcFailureCounts): Collection
    {
        $mappedNpcIds = $mappingVersion->enemies()
            ->whereNotNull('npc_id')
            ->distinct()
            ->pluck('npc_id');

        /** @var Collection<int, Npc> $npcs */
        $npcs = Npc::query()
            ->whereIn('id', $mappedNpcIds->merge($npcFailureCounts->keys())->unique())
            ->get()
            ->sortBy([
                static fn(Npc $a, Npc $b) => ($npcFailureCounts->get($b->id, 0) <=> $npcFailureCounts->get($a->id, 0)),
                static fn(Npc $a, Npc $b) => strcmp(__($a->name), __($b->name)),
            ])
            ->values();

        return $npcs;
    }

    public function combatlogregenerate(SeasonServiceInterface $seasonService): View
    {
        return view('admin.tools.combatlog.regenerate', [
            'seasons' => $this->getSeasonsSelectList(),
            'periods' => $this->getPeriodsSelectList($seasonService),
        ]);
    }

    public function combatlogregeneratesubmit(
        AdminToolsCombatLogRegenerateRequest $request,
        SeasonServiceInterface               $seasonService,
    ): View {
        set_time_limit(3600);

        // Null means "do not limit to any dungeon(s)" / "do not limit to a season" / "do not limit to any week"
        $dungeonIds = $request->getDungeonIds();
        $season     = $request->getSeason();
        $periods    = $request->getPeriods();

        if ($request->deleteEnemyFailures()) {
            CombatLogRouteEnemyFailure::query()
                ->when($dungeonIds !== null, static fn(Builder $builder) => $builder->whereIn('dungeon_id', $dungeonIds))
                ->delete();
        }

        $count = 0;

        // Cannot use joins since the other table lives in a different database
        DungeonRoute::query()
            ->when($dungeonIds !== null, static fn(Builder $builder) => $builder->whereIn('dungeon_id', $dungeonIds))
            ->when($season !== null, static fn(Builder $builder) => $builder->where('season_id', $season->id))
            ->chunkById(200, function (Collection $dungeonRoutes) use (&$count, $periods) {
                $dungeonRoutes = $dungeonRoutes->keyBy('id');
                /** @var Collection<int, ChallengeModeRun> $challengeModes */
                $challengeModes = ChallengeModeRun::whereIn('dungeon_route_id', $dungeonRoutes->pluck('id'))
                    ->when($periods !== null, static fn(Builder $builder) => $builder->whereHas(
                        'challengeModeRunData',
                        static fn(Builder $builder) => $builder->whereIn(
                            // The week a run belongs to only lives inside the stored request body - and a run whose
                            // post_body was pruned cannot be regenerated at all, so dropping it here costs nothing
                            DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(post_body, '$.metadata.period')) AS UNSIGNED)"),
                            $periods,
                        ),
                    ))
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

        return view('admin.tools.combatlog.regenerate', [
            'seasons' => $this->getSeasonsSelectList(),
            'periods' => $this->getPeriodsSelectList($seasonService),
        ]);
    }

    /**
     * Every week of every season that has started, most recent first, keyed by the keystone leaderboard period
     * that week falls in and grouped per season so the select renders one optgroup per season. The period is the
     * number a run carries in its metadata, and is spelled out in the label so it can be cross referenced against
     * the heatmap's week filter.
     *
     * @return array<string, array<int, string>>
     */
    private function getPeriodsSelectList(SeasonServiceInterface $seasonService): array
    {
        $region = AdminToolsCombatLogRegenerateRequest::getPeriodRegion();

        $result = [];
        foreach ($seasonService->getAllSeasons()->sortByDesc('start') as $season) {
            $weeks = [];

            foreach ($seasonService->getSeasonWeeks($season, $region) as $seasonWeek) {
                $weeks[$seasonWeek->period] = __('view_admin.tools.combatlog.regenerate.period_option', [
                    'week'   => $seasonWeek->week,
                    'date'   => $seasonWeek->start->toFormattedDateString(),
                    'period' => $seasonWeek->period,
                ]);
            }

            if ($weeks === []) {
                continue;
            }

            $result[$season->name_long] = array_reverse($weeks, true);
        }

        return $result;
    }

    /**
     * All seasons, most recent first, keyed by id - prefixed with an empty option so the regeneration
     * does not have to be limited to a season at all.
     *
     * @return array<int|string, string>
     */
    private function getSeasonsSelectList(): array
    {
        return ['' => __('view_admin.tools.combatlog.regenerate.season_any')] +
            Season::with(['expansion'])
                ->orderByDesc('id')
                ->get()
                ->mapWithKeys(static fn(Season $season) => [$season->id => $season->name_long])
                ->toArray();
    }
}
