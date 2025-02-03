<?php

namespace App\Http\Controllers\Dungeon;

use App\Features\Heatmap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Heatmap\ExploreEmbedUrlFormRequest;
use App\Http\Requests\Heatmap\ExploreUrlFormRequest;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
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
        ExploreUrlFormRequest          $request,
        MapContextServiceInterface     $mapContextService,
        CombatLogEventServiceInterface $combatLogEventService,
        SeasonServiceInterface         $seasonService,
        GameVersion                    $gameVersion,
        Dungeon                        $dungeon,
        string                         $floorIndex = '1'
    ): View|RedirectResponse {
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
                ] + $request->validated());
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeon.explore.gameversion.view.floor', [
                        'gameVersion' => $gameVersion,
                        'dungeon'     => $dungeon,
                        'floorIndex'  => $floor->index,
                    ] + $request->validated());
            }

            $mostRecentSeason = $dungeon->getActiveSeason($seasonService);

            $heatmapActive = Feature::active(Heatmap::class) && $dungeon->heatmap_enabled;

            $dungeon->trackPageView(Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON);

            return view('dungeon.explore.gameversion.view', array_merge($this->getFilterSettings($mostRecentSeason), [
                'gameVersion'             => $gameVersion,
                'season'                  => $mostRecentSeason,
                'dungeon'                 => $dungeon,
                'floor'                   => $floor,
                'title'                   => __($dungeon->name),
                'mapContext'              => $mapContextService->createMapContextDungeonExplore($dungeon, $floor, $dungeon->currentMappingVersion),
                'showHeatmapSearch'       => $heatmapActive,
                'seasonWeeklyAffixGroups' => $dungeon->gameVersion->has_seasons ?
                    $seasonService->getWeeklyAffixGroupsSinceStart($mostRecentSeason, GameServerRegion::getUserOrDefaultRegion()) :
                    collect(),
            ]));
        }
    }

    public function embed(
        ExploreEmbedUrlFormRequest $request,
        MapContextServiceInterface $mapContextService,
        SeasonServiceInterface     $seasonService,
        GameVersion                $gameVersion,
        Dungeon                    $dungeon,
        string                     $floorIndex = '1'
    ): View|RedirectResponse {
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

            return redirect()->route('dungeon.explore.gameversion.embed.floor', [
                    'gameVersion' => $gameVersion,
                    'dungeon'     => $dungeon,
                    'floorIndex'  => $defaultFloor?->index ?? '1',
                ] + $request->validated());
        } else if ($floor->index !== (int)$floorIndex) {
            return redirect()->route('dungeon.explore.gameversion.embed.floor', [
                    'gameVersion' => $gameVersion,
                    'dungeon'     => $dungeon,
                    'floorIndex'  => $floor->index,
                ] + $request->validated());
        }


        $style                 = $request->get('style', 'compact');
        $headerBackgroundColor = $request->get('headerBackgroundColor');
        $mapBackgroundColor    = $request->get('mapBackgroundColor');
        $showEnemyInfo         = $request->get('showEnemyInfo', false);
        $showTitle             = $request->get('showTitle', true);
        $defaultZoom           = $request->get('defaultZoom', 1);

        $parameters = [
            'type'             => $request->get('type'),
            'dataType'         => $request->get('dataType'),
            'minMythicLevel'   => $request->get('minMythicLevel'),
            'maxMythicLevel'   => $request->get('maxMythicLevel'),
            'includeAffixIds'  => $request->get('includeAffixIds'),
            'minPeriod'        => $request->get('minPeriod'),
            'maxPeriod'        => $request->get('maxPeriod'),
            'minTimerFraction' => $request->get('minTimerFraction'),
            'maxTimerFraction' => $request->get('maxTimerFraction'),
        ];

        $mostRecentSeason = $dungeon->getActiveSeason($seasonService);

        $heatmapActive = Feature::active(Heatmap::class) && $dungeon->heatmap_enabled;

        $dungeon->trackPageView(Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON_EMBED);

        return view('dungeon.explore.gameversion.embed', array_merge($this->getFilterSettings($mostRecentSeason), [
            'gameVersion'             => $gameVersion,
            'season'                  => $mostRecentSeason,
            'dungeon'                 => $dungeon,
            'floor'                   => $floor,
            'title'                   => __($dungeon->name),
            'mapContext'              => $mapContextService->createMapContextDungeonExplore($dungeon, $floor, $dungeon->currentMappingVersion),
            'showHeatmapSearch'       => $heatmapActive,
            'seasonWeeklyAffixGroups' => $dungeon->gameVersion->has_seasons ?
                $seasonService->getWeeklyAffixGroupsSinceStart($mostRecentSeason, GameServerRegion::getUserOrDefaultRegion()) :
                collect(),
            'parameters'              => $parameters,
            'defaultZoom'             => $defaultZoom,
            'embedOptions'            => [
                'style'                 => $style,
                'headerBackgroundColor' => $headerBackgroundColor,
                'mapBackgroundColor'    => $mapBackgroundColor,
                'show'                  => [
                    'enemyInfo'      => (bool)$showEnemyInfo, // Default false - not available
                    'title'          => $showTitle,
                    'floorSelection' => true,                 // Always available, but can be overridden later if there's no floors to select
                ],
            ],
        ]));
    }

    private function getFilterSettings(?Season $season): array
    {
        return [
            'keyLevelMin'           => $season?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'),
            'keyLevelMax'           => $season?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'),
            'itemLevelMin'          => $season?->item_level_min ?? 0,
            'itemLevelMax'          => $season?->item_level_max ?? 0,
            'playerDeathsMin'       => 0,
            'playerDeathsMax'       => 99,
            'minSamplesRequiredMin' => 1,
            'minSamplesRequiredMax' => 100,
        ];
    }
}
