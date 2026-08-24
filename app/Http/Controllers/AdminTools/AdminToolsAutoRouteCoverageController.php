<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminToolsAutoRouteCoverageRequest;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Overview of how close the Auto Route Creator's output gets to 100% enemy forces, per dungeon of the current season.
 *
 * A route is Auto Route Creator output if - and only if - a ChallengeModeRun exists that points at it. Since those runs
 * live on the combatlog connection and dungeon_routes does not, the two cannot be joined and are queried separately.
 */
class AdminToolsAutoRouteCoverageController extends Controller
{
    /** Below this percentage something is structurally wrong with the route or the mapping. */
    private const PERCENTAGE_CRITICAL = 95.0;

    /** Below this percentage the route is worth a look, but a handful of enemies is a plausible explanation. */
    private const PERCENTAGE_WARNING = 99.0;

    /** How many of the worst routes of a dungeon are listed in its drilldown. */
    private const MAX_DETAIL_ROUTES = 25;

    public function index(
        AdminToolsAutoRouteCoverageRequest $request,
        SeasonServiceInterface             $seasonService,
    ): View {
        $days   = $request->getDays();
        $season = $seasonService->getCurrentSeason();

        if ($season === null) {
            return view('admin.tools.combatlog.route.coverage', [
                'season'   => null,
                'days'     => $days,
                'dungeons' => collect(),
            ]);
        }

        /** @var Collection<int, Dungeon> $dungeons */
        $dungeons = $season->dungeons()->get()->keyBy('id');

        $challengeModeRuns = $this->getChallengeModeRuns($dungeons->keys()->all(), $days);
        $dungeonRoutes     = $this->getDungeonRoutes($challengeModeRuns->pluck('dungeon_route_id')->all());
        $enemyFailures     = $this->getEnemyFailureCounts($dungeonRoutes->keys()->all());

        return view('admin.tools.combatlog.route.coverage', [
            'season'   => $season,
            'days'     => $days,
            'dungeons' => $this->buildDungeonOverview($dungeons, $challengeModeRuns, $dungeonRoutes, $enemyFailures),
        ]);
    }

    /**
     * @param array<int> $dungeonIds
     *
     * @return Collection<int, ChallengeModeRun> keyed by dungeon route id
     */
    private function getChallengeModeRuns(array $dungeonIds, int $days): Collection
    {
        if ($dungeonIds === []) {
            return collect();
        }

        return ChallengeModeRun::query()
            // ChallengeModeRun eager loads its ChallengeModeRunData by default, whose post_body is a ~100KB mediumtext.
            // Loading that for thousands of runs would read hundreds of megabytes that this page never displays.
            ->without('challengeModeRunData')
            ->select(['id', 'dungeon_id', 'dungeon_route_id', 'level', 'duplicate', 'created_at'])
            ->whereIn('dungeon_id', $dungeonIds)
            // Depleted or abandoned runs legitimately never reached 100% - counting them would drown out actual issues
            ->where('success', true)
            ->whereNotNull('dungeon_route_id')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->get()
            ->keyBy('dungeon_route_id');
    }

    /**
     * @param array<int> $dungeonRouteIds
     *
     * @return Collection<int, DungeonRoute> keyed by dungeon route id
     */
    private function getDungeonRoutes(array $dungeonRouteIds): Collection
    {
        if ($dungeonRouteIds === []) {
            return collect();
        }

        return DungeonRoute::query()
            ->select(['id', 'public_key', 'dungeon_id', 'mapping_version_id', 'title', 'enemy_forces'])
            ->with(['dungeon', 'mappingVersion'])
            ->whereIn('id', $dungeonRouteIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * The amount of enemies the Auto Route Creator could not place on the map, per dungeon route. This is the most
     * direct explanation available for a route that did not reach 100%.
     *
     * @param array<int> $dungeonRouteIds
     *
     * @return Collection<int, int> enemy failure count, keyed by dungeon route id
     */
    private function getEnemyFailureCounts(array $dungeonRouteIds): Collection
    {
        if ($dungeonRouteIds === []) {
            return collect();
        }

        return CombatLogRouteEnemyFailure::query()
            ->selectRaw('dungeon_route_id, COUNT(*) AS failure_count')
            ->whereIn('dungeon_route_id', $dungeonRouteIds)
            ->groupBy('dungeon_route_id')
            ->pluck('failure_count', 'dungeon_route_id');
    }

    /**
     * @param Collection<int, Dungeon>          $dungeons
     * @param Collection<int, ChallengeModeRun> $challengeModeRuns
     * @param Collection<int, DungeonRoute>     $dungeonRoutes
     * @param Collection<int, int>              $enemyFailures
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function buildDungeonOverview(
        Collection $dungeons,
        Collection $challengeModeRuns,
        Collection $dungeonRoutes,
        Collection $enemyFailures,
    ): Collection {
        $routesByDungeonId = $dungeonRoutes->groupBy('dungeon_id');

        $rows = [];
        foreach ($dungeons as $dungeon) {
            $rows[] = $this->buildDungeonRow(
                $dungeon,
                collect($routesByDungeonId->get($dungeon->id, collect())),
                $challengeModeRuns,
                $enemyFailures,
            );
        }

        return collect($rows)
            ->sortByDesc(static fn(array $row): array => [$row['problemPercentage'], $row['buckets']['critical'], $row['total']])
            ->values();
    }

    /**
     * @param Collection<int, DungeonRoute>     $dungeonRoutes     the Auto Route Creator routes of this dungeon
     * @param Collection<int, ChallengeModeRun> $challengeModeRuns keyed by dungeon route id
     * @param Collection<int, int>              $enemyFailures     keyed by dungeon route id
     *
     * @return array<string, mixed>
     */
    private function buildDungeonRow(
        Dungeon    $dungeon,
        Collection $dungeonRoutes,
        Collection $challengeModeRuns,
        Collection $enemyFailures,
    ): array {
        $buckets = [
            'critical' => 0,
            'warning'  => 0,
            'ok'       => 0,
            'over'     => 0,
            'unknown'  => 0,
        ];

        $routes = [];
        foreach ($dungeonRoutes as $dungeonRoute) {
            $route = $this->buildRouteRow(
                $dungeonRoute,
                $challengeModeRuns->get($dungeonRoute->id),
                (int)$enemyFailures->get($dungeonRoute->id, 0),
            );

            $buckets[$route['bucket']]++;
            $routes[] = $route;
        }

        // Routes without a known percentage are sorted last - they say nothing about the Auto Route Creator
        usort($routes, static fn(array $a, array $b): int => ($a['percentage'] ?? PHP_FLOAT_MAX) <=> ($b['percentage'] ?? PHP_FLOAT_MAX));

        $total = count($routes);

        return [
            'dungeon'           => $dungeon,
            'total'             => $total,
            'buckets'           => $buckets,
            'problemPercentage' => $total > 0 ? round((($buckets['critical'] + $buckets['warning']) / $total) * 100, 1) : 0.0,
            'routes'            => collect(array_slice($routes, 0, self::MAX_DETAIL_ROUTES)),
            'hiddenRouteCount'  => max(0, $total - self::MAX_DETAIL_ROUTES),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRouteRow(
        DungeonRoute      $dungeonRoute,
        ?ChallengeModeRun $challengeModeRun,
        int               $enemyFailureCount,
    ): array {
        $required   = $dungeonRoute->mappingVersion->enemy_forces_required;
        $percentage = $required > 0 ? round(($dungeonRoute->enemy_forces / $required) * 100, 2) : null;

        return [
            'dungeonRoute'        => $dungeonRoute,
            'percentage'          => $percentage,
            'bucket'              => $this->getBucket($percentage),
            'enemyForcesRequired' => $required,
            'enemyFailureCount'   => $enemyFailureCount,
            'level'               => $challengeModeRun?->level,
            'duplicate'           => (bool)$challengeModeRun?->duplicate,
            'createdAt'           => $challengeModeRun?->created_at,
        ];
    }

    /**
     * @return 'critical'|'warning'|'ok'|'over'|'unknown'
     */
    private function getBucket(?float $percentage): string
    {
        return match (true) {
            $percentage === null                     => 'unknown',
            $percentage > 100.0                      => 'over',
            $percentage >= self::PERCENTAGE_WARNING  => 'ok',
            $percentage >= self::PERCENTAGE_CRITICAL => 'warning',
            default                                  => 'critical',
        };
    }
}
