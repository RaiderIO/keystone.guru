<?php

namespace App\Http\Controllers\Dungeon;

use App\Features\Heatmap;
use App\Http\Controllers\Controller;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class DungeonExploreController extends Controller
{
    public function get(Request $request, CombatLogEventServiceInterface $combatLogEventService): View
    {
        return view('dungeon.explore.list', [
            'runCountPerDungeon' => Feature::active(Heatmap::class) ? $combatLogEventService->getRunCountPerDungeon() : collect(),
        ]);
    }

    public function viewDungeon(Request $request, Dungeon $dungeon): RedirectResponse
    {
        $dungeon->load(['currentMappingVersion']);

        if (!$dungeon->active || $dungeon->currentMappingVersion === null) {
            return redirect()->route('dungeon.explore.list');
        }

        $dungeon->load(['currentMappingVersion']);

        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
            ->defaultOrFacade($dungeon->currentMappingVersion)
            ->first();

        return redirect()->route('dungeon.explore.view.floor', [
            'dungeon'    => $dungeon,
            'floorIndex' => $defaultFloor?->index ?? '1',
        ]);
    }

    public function viewDungeonFloor(
        Request                        $request,
        MapContextServiceInterface     $mapContextService,
        CombatLogEventServiceInterface $combatLogEventService,
        SeasonServiceInterface         $seasonService,
        Dungeon                        $dungeon,
        string                         $floorIndex = '1'): View|RedirectResponse
    {
        $dungeon->load(['currentMappingVersion']);

        if (!$dungeon->active || $dungeon->currentMappingVersion === null) {
            return redirect()->route('dungeon.explore.list');
        }

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)
            ->indexOrFacade($dungeon->currentMappingVersion, $floorIndex)
            ->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
                ->defaultOrFacade($dungeon->currentMappingVersion)
                ->first();

            return redirect()->route('dungeon.explore.view.floor', [
                'dungeon'    => $dungeon,
                'floorIndex' => $defaultFloor?->index ?? '1',
            ]);
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeon.explore.view.floor', [
                    'dungeon'    => $dungeon,
                    'floorIndex' => $floor->index,
                ]);
            }

            $combatLogEventFilter = new CombatLogEventFilter(
                $seasonService,
                $dungeon,
                CombatLogEventEventType::EnemyKilled,
                CombatLogEventDataType::PlayerPosition,
            );

            $mostRecentSeason = $dungeon->getActiveSeason($seasonService);

            $heatmapActive = Feature::active(Heatmap::class) &&
                $dungeon->gameVersion->has_seasons &&
                $dungeon->challenge_mode_id !== null;

            $dungeon->trackPageView();

            return view('dungeon.explore.view', [
                'dungeon'                 => $dungeon,
                'floor'                   => $floor,
                'title'                   => __($dungeon->name),
                'mapContext'              => $mapContextService->createMapContextDungeonExplore($dungeon, $floor, $dungeon->currentMappingVersion),
                'showHeatmapSearch'       => $heatmapActive && $combatLogEventService->getRunCount($combatLogEventFilter),
                'availableDateRange'      => $heatmapActive ? $combatLogEventService->getAvailableDateRange($combatLogEventFilter) : null,
                'keyLevelMin'             => $mostRecentSeason?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'),
                'keyLevelMax'             => $mostRecentSeason?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'),
                'seasonWeeklyAffixGroups' => $dungeon->gameVersion->has_seasons ?
                    $seasonService->getWeeklyAffixGroupsSinceStart($mostRecentSeason, GameServerRegion::getUserOrDefaultRegion()) :
                    collect(),
            ]);
        }
    }
}
