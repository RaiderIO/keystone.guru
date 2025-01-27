<?php

namespace App\Http\Controllers\Dungeon;

use App\Features\Heatmap;
use App\Http\Controllers\Controller;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
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
    public function get(Request $request): RedirectResponse
    {
        return redirect()->route('dungeon.explore.gameversion.list', [
            'gameVersion' => GameVersion::getUserOrDefaultGameVersion(),
        ]);
    }

    public function getByGameVersion(Request $request, GameVersion $gameVersion, CombatLogEventServiceInterface $combatLogEventService): View|RedirectResponse
    {
        if ($gameVersion->id !== GameVersion::getUserOrDefaultGameVersion()->id) {
            return redirect()->route('dungeon.explore.gameversion.list', [
                'gameVersion' => GameVersion::getUserOrDefaultGameVersion(),
            ]);
        }

        return view('dungeon.explore.gameversion.list', [
            'showRunCountPerDungeon' => Feature::active(Heatmap::class),
            'gameVersion'            => $gameVersion,
        ]);
    }

    public function viewDungeon(Request $request, GameVersion $gameVersion, Dungeon $dungeon): RedirectResponse
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

        return redirect()->route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor?->index ?? '1',
        ]);
    }

    public function viewDungeonFloor(
        Request                        $request,
        MapContextServiceInterface     $mapContextService,
        CombatLogEventServiceInterface $combatLogEventService,
        SeasonServiceInterface         $seasonService,
        GameVersion                    $gameVersion,
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

            return redirect()->route('dungeon.explore.gameversion.view.floor', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $defaultFloor?->index ?? '1',
            ]);
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeon.explore.gameversion.view.floor', [
                    'gameVersion' => $gameVersion,
                    'dungeon'     => $dungeon,
                    'floorIndex'  => $floor->index,
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

            return view('dungeon.explore.gameversion.view', [
                'gameVersion'             => $gameVersion,
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
