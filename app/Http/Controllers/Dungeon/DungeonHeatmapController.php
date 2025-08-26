<?php

namespace App\Http\Controllers\Dungeon;

use App\Features\Heatmap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Heatmap\HeatmapEmbedUrlFormRequest;
use App\Http\Requests\Heatmap\HeatmapUrlFormRequest;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Season;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class DungeonHeatmapController extends Controller
{
    public function get(
        Request                     $request,
        GameVersionServiceInterface $gameVersionService
    ): RedirectResponse {
        return redirect()->route('dungeon.heatmaps.gameversion.list', [
            'gameVersion' => $gameVersionService->getGameVersion(Auth::user()),
        ]);
    }

    public function getByGameVersion(
        Request                     $request,
        GameVersion                 $gameVersion,
        GameVersionServiceInterface $gameVersionService
    ): View|RedirectResponse {
        $userOrDefaultGameVersion = $gameVersionService->getGameVersion(Auth::user());
        if ($gameVersion->id !== $userOrDefaultGameVersion->id) {
            return redirect()->route('dungeon.heatmaps.gameversion.list', [
                'gameVersion' => $userOrDefaultGameVersion,
            ]);
        }

        return view('dungeon.heatmap.gameversion.list', [
            'gameVersion' => $gameVersion,
        ]);
    }

    public function viewDungeon(Request $request, GameVersion $gameVersion, Dungeon $dungeon): RedirectResponse
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        $redirect = $this->guardAgainstInvalidAccess($dungeon, $currentMappingVersion);
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
            ->defaultOrFacade($currentMappingVersion)
            ->first();

        return redirect()->route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor?->index ?? '1',
        ]);
    }

    public function viewDungeonFloorMechagonWorkshopCorrection(
        HeatmapUrlFormRequest $request,
        string                $floorIndex = '1'
    ): RedirectResponse {
        return redirect()->route('dungeon.heatmap.gameversion.view.floor', [
                'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
                'dungeon'     => Dungeon::where('key', Dungeon::DUNGEON_MECHAGON_WORKSHOP)->firstOrFail(),
                'floorIndex'  => $floorIndex,
            ] + $request->validated());
    }

    public function viewDungeonFloor(
        HeatmapUrlFormRequest      $request,
        MapContextServiceInterface $mapContextService,
        SeasonServiceInterface     $seasonService,
        GameVersion                $gameVersion,
        Dungeon                    $dungeon,
        string                     $floorIndex = '1'
    ): View|RedirectResponse {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        $redirect = $this->guardAgainstInvalidAccess($dungeon, $currentMappingVersion);
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
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

            return redirect()->route('dungeon.heatmap.gameversion.view.floor', [
                    'gameVersion' => $gameVersion,
                    'dungeon'     => $dungeon,
                    'floorIndex'  => $defaultFloor?->index ?? '1',
                ] + $request->validated());
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeon.heatmap.gameversion.view.floor', [
                        'gameVersion' => $gameVersion,
                        'dungeon'     => $dungeon,
                        'floorIndex'  => $floor->index,
                    ] + $request->validated());
            }

            $mostRecentSeason = $dungeon->getActiveSeason($seasonService);

            $dungeon->trackPageView(Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON);

            return view('dungeon.heatmap.gameversion.view', array_merge($this->getFilterSettings($mostRecentSeason), [
                'gameVersion'             => $gameVersion,
                'season'                  => $mostRecentSeason,
                'dungeon'                 => $dungeon,
                'floor'                   => $floor,
                'title'                   => __($dungeon->name),
                'mapContext'              => $mapContextService->createMapContextDungeonExplore($dungeon, $floor, $currentMappingVersion),
                'seasonWeeklyAffixGroups' => $dungeon->hasMappingVersionWithSeasons() && $mostRecentSeason !== null ?
                    $seasonService->getWeeklyAffixGroupsSinceStart($mostRecentSeason, GameServerRegion::getUserOrDefaultRegion()) :
                    collect(),
            ]));
        }
    }

    public function embedMechagonWorkshopCorrection(
        HeatmapUrlFormRequest $request,
        string                $floorIndex = '1'
    ): RedirectResponse {
        return redirect()->route('dungeon.heatmap.gameversion.embed.floor', [
                'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
                'dungeon'     => Dungeon::where('key', Dungeon::DUNGEON_MECHAGON_WORKSHOP)->firstOrFail(),
                'floorIndex'  => $floorIndex,
            ] + $request->validated());
    }

    public function embed(
        HeatmapEmbedUrlFormRequest $request,
        MapContextServiceInterface $mapContextService,
        SeasonServiceInterface     $seasonService,
        GameVersion                $gameVersion,
        Dungeon                    $dungeon,
        string                     $floorIndex = '1'
    ): View|RedirectResponse {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        $redirect = $this->guardAgainstInvalidAccess($dungeon, $currentMappingVersion);
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
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

            return redirect()->route('dungeon.heatmap.gameversion.embed.floor', [
                    'gameVersion' => $gameVersion,
                    'dungeon'     => $dungeon,
                    'floorIndex'  => $defaultFloor?->index ?? '1',
                ] + $validated);
        } else if ($floor->index !== (int)$floorIndex) {
            return redirect()->route('dungeon.heatmap.gameversion.embed.floor', [
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
        $showSidebar           = $request->get('showSidebar', true);
        $defaultZoom           = $request->get('defaultZoom', 1);

        unset(
            $validated['style'],
            $validated['headerBackgroundColor'],
            $validated['mapBackgroundColor'],
            $validated['showEnemyInfo'],
            $validated['showTitle'],
            $validated['showSidebar'],
            $validated['defaultZoom']
        );

        $mostRecentSeason = $dungeon->getActiveSeason($seasonService);

        $heatmapActive = Feature::active(Heatmap::class) && $dungeon->heatmap_enabled;

        $dungeon->trackPageView(Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON_EMBED);

        return view('dungeon.heatmap.gameversion.embed', array_merge($this->getFilterSettings($mostRecentSeason), [
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
                    'title'          => (bool)$showTitle,
                    'sidebar'        => (bool)$showSidebar,
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

    /**
     * Maybe this should go in a policy?
     *
     * @param Dungeon             $dungeon
     * @param MappingVersion|null $currentMappingVersion
     * @return RedirectResponse|null
     */
    private function guardAgainstInvalidAccess(Dungeon $dungeon, ?MappingVersion $currentMappingVersion): ?RedirectResponse
    {
        if (
            !$dungeon->active ||
            !$dungeon->heatmap_enabled ||
            $currentMappingVersion === null ||
            !Feature::active(Heatmap::class)
        ) {
            return redirect()->route('dungeon.heatmaps.list');
        }

        return null;
    }
}
