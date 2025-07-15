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
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class DungeonExploreController extends Controller
{

    public function get(
        Request                     $request,
        GameVersionServiceInterface $gameVersionService
    ): RedirectResponse {
        return redirect()->route('dungeon.explore.gameversion.list', [
            'gameVersion' => $gameVersionService->getGameVersion(Auth::user()),
        ]);
    }

    public function getByGameVersion(
        Request                        $request,
        GameVersion                    $gameVersion,
        CombatLogEventServiceInterface $combatLogEventService,
        GameVersionServiceInterface    $gameVersionService
    ): View|RedirectResponse {
        $userOrDefaultGameVersion = $gameVersionService->getGameVersion(Auth::user());
        if ($gameVersion->id !== $userOrDefaultGameVersion->id) {
            return redirect()->route('dungeon.explore.gameversion.list', [
                'gameVersion' => $userOrDefaultGameVersion,
            ]);
        }

        return view('dungeon.explore.gameversion.list', [
            'showRunCountPerDungeon' => Feature::active(Heatmap::class),
            'gameVersion'            => $gameVersion,
        ]);
    }

    public function viewDungeon(Request $request, GameVersion $gameVersion, Dungeon $dungeon): RedirectResponse
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        if (!$dungeon->active || $currentMappingVersion === null) {
            return redirect()->route('dungeon.explore.list');
        }

        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
            ->defaultOrFacade($currentMappingVersion)
            ->first();

        return redirect()->route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor?->index ?? '1',
        ]);
    }

    public function viewDungeonFloorMechagonWorkshopCorrection(
        ExploreUrlFormRequest $request,
        string                $floorIndex = '1'
    ): RedirectResponse {
        return redirect()->route('dungeon.explore.gameversion.view.floor', [
                'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
                'dungeon'     => Dungeon::where('key', Dungeon::DUNGEON_MECHAGON_WORKSHOP)->firstOrFail(),
                'floorIndex'  => $floorIndex,
            ] + $request->validated());
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
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        if (!$dungeon->active || $currentMappingVersion === null) {
            return redirect()->route('dungeon.explore.list');
        }

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)
            ->indexOrFacade($currentMappingVersion, $floorIndex)
            ->first();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
                ->defaultOrFacade($currentMappingVersion)
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
                'mapContext'              => $mapContextService->createMapContextDungeonExplore($dungeon, $floor, $currentMappingVersion),
                'showHeatmapSearch'       => $heatmapActive,
                'seasonWeeklyAffixGroups' => $dungeon->hasMappingVersionWithSeasons() && $mostRecentSeason !== null ?
                    $seasonService->getWeeklyAffixGroupsSinceStart($mostRecentSeason, GameServerRegion::getUserOrDefaultRegion()) :
                    collect(),
            ]));
        }
    }

    public function embedMechagonWorkshopCorrection(
        ExploreUrlFormRequest $request,
        string                $floorIndex = '1'
    ): RedirectResponse {
        return redirect()->route('dungeon.explore.gameversion.embed.floor', [
                'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
                'dungeon'     => Dungeon::where('key', Dungeon::DUNGEON_MECHAGON_WORKSHOP)->firstOrFail(),
                'floorIndex'  => $floorIndex,
            ] + $request->validated());
    }

    public function embed(
        ExploreEmbedUrlFormRequest $request,
        MapContextServiceInterface $mapContextService,
        SeasonServiceInterface     $seasonService,
        GameVersion                $gameVersion,
        Dungeon                    $dungeon,
        string                     $floorIndex = '1'
    ): View|RedirectResponse {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        if (!$dungeon->active || $currentMappingVersion === null) {
            return redirect()->route('dungeon.explore.list');
        }

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)
            ->indexOrFacade($currentMappingVersion, $floorIndex)
            ->first();

        $validated = $request->validated();

        if ($floor === null) {
            /** @var Floor $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
                ->defaultOrFacade($currentMappingVersion)
                ->first();

            return redirect()->route('dungeon.explore.gameversion.embed.floor', [
                    'gameVersion' => $gameVersion,
                    'dungeon'     => $dungeon,
                    'floorIndex'  => $defaultFloor?->index ?? '1',
                ] + $validated);
        } else if ($floor->index !== (int)$floorIndex) {
            return redirect()->route('dungeon.explore.gameversion.embed.floor', [
                    'gameVersion' => $gameVersion,
                    'dungeon'     => $dungeon,
                    'floorIndex'  => $floor->index,
                ] + $validated);
        }


        $style                 = $request->get('style', 'compact');
        $headerBackgroundColor = $request->get('headerBackgroundColor');
        $mapBackgroundColor    = $request->get('mapBackgroundColor');
        $showEnemyInfo         = $request->get('showEnemyInfo', false);
        $showTitle             = $request->get('showTitle', true);
        $defaultZoom           = $request->get('defaultZoom', 1);

        unset(
            $validated['style'],
            $validated['headerBackgroundColor'],
            $validated['mapBackgroundColor'],
            $validated['showEnemyInfo'],
            $validated['showTitle'],
            $validated['defaultZoom']
        );

        $mostRecentSeason = $dungeon->getActiveSeason($seasonService);

        $heatmapActive = Feature::active(Heatmap::class) && $dungeon->heatmap_enabled;

        $dungeon->trackPageView(Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON_EMBED);

        return view('dungeon.explore.gameversion.embed', array_merge($this->getFilterSettings($mostRecentSeason), [
            'gameVersion'             => $gameVersion,
            'season'                  => $mostRecentSeason,
            'dungeon'                 => $dungeon,
            'floor'                   => $floor,
            'title'                   => __($dungeon->name),
            'mapContext'              => $mapContextService->createMapContextDungeonExplore($dungeon, $floor, $currentMappingVersion),
            'showHeatmapSearch'       => $heatmapActive,
            'seasonWeeklyAffixGroups' => $dungeon->hasMappingVersionWithSeasons() ?
                $seasonService->getWeeklyAffixGroupsSinceStart($mostRecentSeason, GameServerRegion::getUserOrDefaultRegion()) :
                collect(),
            'parameters'              => $validated,
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
