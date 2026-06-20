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
use App\Models\User;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Laravel\Pennant\Feature;

class DungeonHeatmapController extends Controller
{
    public function get(
        Request                     $request,
        GameVersionServiceInterface $gameVersionService,
    ): RedirectResponse {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return redirect()->route('dungeon.heatmap.gameversion', [
            'gameVersion' => $gameVersionService->getGameVersion($user),
        ]);
    }

    public function select(
        Request                     $request,
        GameVersion                 $gameVersion,
        GameVersionServiceInterface $gameVersionService,
    ): View {
        return view('dungeon.heatmap.gameversion.list', [
            'gameVersion' => $gameVersion,
        ]);
    }

    public function getByGameVersion(
        Request                     $request,
        GameVersion                 $gameVersion,
        GameVersionServiceInterface $gameVersionService,
    ): RedirectResponse {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $userOrDefaultGameVersion = $gameVersionService->getGameVersion($user);
        if ($gameVersion->id !== $userOrDefaultGameVersion->id) {
            return redirect()->route('dungeon.heatmap.gameversion.select', [
                'gameVersion' => $userOrDefaultGameVersion,
            ]);
        }

        $contextDungeon = Dungeon::getUserOrDefaultDungeon();

        return redirect()->route('dungeon.heatmap.gameversion.view', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $contextDungeon,
        ]);
    }

    public function viewDungeon(
        SeasonServiceInterface $seasonService,
        Request                $request,
        GameVersion            $gameVersion,
        Dungeon                $dungeon,
    ): RedirectResponse {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        $redirect = $this->guardAgainstInvalidAccess($gameVersion, $dungeon, $currentMappingVersion, $dungeon->getActiveSeason($seasonService));
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        /** @var Floor|null $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
            ->defaultOrFacade($currentMappingVersion)
            ->first();

        return redirect()->route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor->index,
        ]);
    }

    public function viewDungeonFloorMechagonWorkshopCorrection(
        HeatmapUrlFormRequest $request,
        string                $floorIndex = '1',
    ): RedirectResponse {
        return redirect()->route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
            'dungeon'     => Dungeon::where('key', Dungeon::DUNGEON_MECHAGON_WORKSHOP)->firstOrFail(),
            'floorIndex'  => $floorIndex,
        ] + $request->validated());
    }

    public function viewDungeonFloor(
        HeatmapUrlFormRequest            $request,
        MapContextServiceInterface       $mapContextService,
        SeasonServiceInterface           $seasonService,
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
        DungeonServiceInterface          $dungeonService,
        GameVersion                      $gameVersion,
        Dungeon                          $dungeon,
        string                           $floorIndex = '1',
    ): View|RedirectResponse {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        $seasonString     = $request->get('season');
        $mostRecentSeason = $seasonService->getSeasonFromShortString($seasonString) ??
            $dungeon->getActiveSeason($seasonService);

        $redirect = $this->guardAgainstInvalidAccess($gameVersion, $dungeon, $currentMappingVersion, $mostRecentSeason);
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        /** @var Floor|null $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)
            ->indexOrFacade($currentMappingVersion, (int)$floorIndex)
            ->first();

        if ($floor === null) {
            /** @var Floor|null $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
                ->defaultOrFacade($currentMappingVersion)
                ->first();

            return redirect()->route('dungeon.heatmap.gameversion.view.floor', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $defaultFloor->index,
            ] + $request->validated());
        } else {
            if ($floor->index !== (int)$floorIndex) {
                return redirect()->route('dungeon.heatmap.gameversion.view.floor', [
                    'gameVersion' => $gameVersion,
                    'dungeon'     => $dungeon,
                    'floorIndex'  => $floor->index,
                ] + $request->validated());
            }

            $dungeon->trackPageView(Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON);

            /** @var \App\Models\User|null $user */
            $user = Auth::user();

            $dungeonService->setDungeonContext($dungeon, $user);

            return view('dungeon.heatmap.gameversion.view', array_merge($this->getFilterSettings($mostRecentSeason), [
                'gameVersion'             => $gameVersion,
                'season'                  => $mostRecentSeason,
                'dungeon'                 => $dungeon,
                'floor'                   => $floor,
                'title'                   => __($dungeon->name),
                'mapContext'              => $mapContextService->createMapContextDungeonExplore($dungeon, $currentMappingVersion, User::getCurrentUserMapFacadeStyle()),
                'seasonWeeklyAffixGroups' => $dungeon->hasMappingVersionWithSeasons() && $mostRecentSeason !== null ?
                    $seasonAffixGroupService->getWeeklyAffixGroupsSinceStart($mostRecentSeason, GameServerRegion::getUserOrDefaultRegion()) :
                    collect(),
                'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion($gameVersion)->filter(fn(Dungeon $dungeon) => $dungeon->heatmap_enabled),
            ]));
        }
    }

    public function embedMechagonWorkshopCorrection(
        HeatmapUrlFormRequest $request,
        string                $floorIndex = '1',
    ): RedirectResponse {
        return redirect()->route('dungeon.heatmap.gameversion.embed.floor', [
            'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
            'dungeon'     => Dungeon::where('key', Dungeon::DUNGEON_MECHAGON_WORKSHOP)->firstOrFail(),
            'floorIndex'  => $floorIndex,
        ] + $request->validated());
    }

    public function embed(
        HeatmapEmbedUrlFormRequest       $request,
        MapContextServiceInterface       $mapContextService,
        SeasonServiceInterface           $seasonService,
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
        GameVersion                      $gameVersion,
        Dungeon                          $dungeon,
        string                           $floorIndex = '1',
    ): View|RedirectResponse {
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        $redirect = $this->guardAgainstInvalidAccess($gameVersion, $dungeon, $currentMappingVersion, $dungeon->getActiveSeason($seasonService));
        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        if (!is_numeric($floorIndex)) {
            $floorIndex = '1';
        }

        // Ensure that User::getCurrentUserMapFacadeStyle() returns the wanted map facade style
        $mapFacadeStyle = $request->get('mapFacadeStyle', User::getCurrentUserMapFacadeStyle());
        User::forceMapFacadeStyle($mapFacadeStyle);

        /** @var Floor|null $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)
            ->indexOrFacade($currentMappingVersion, (int)$floorIndex)
            ->first();

        $validated = $request->validated();

        if ($floor === null) {
            /** @var Floor|null $defaultFloor */
            $defaultFloor = Floor::where('dungeon_id', $dungeon->id)
                ->defaultOrFacade($currentMappingVersion)
                ->first();

            return redirect()->route('dungeon.heatmap.gameversion.embed.floor', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $defaultFloor->index,
            ] + $validated);
        } elseif ($floor->index !== (int)$floorIndex) {
            return redirect()->route('dungeon.heatmap.gameversion.embed.floor', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $floor->index,
            ] + $validated);
        }

        $locale = $request->get('locale', App::getLocale());
        App::setLocale(
            config('language.short_to_long')[$locale] ?? $locale,
        );

        $style                  = $request->get('style', 'compact');
        $headerBackgroundColor  = $request->get('headerBackgroundColor');
        $mapBackgroundColor     = $request->get('mapBackgroundColor');
        $showEnemyInfo          = $request->get('showEnemyInfo', false);
        $showTitle              = $request->get('showTitle', true);
        $showSidebar            = $request->get('showSidebar', true);
        $showHeader             = $request->get('showHeader', true);
        $showDataSourceSnackbar = $request->get('showDataSourceSnackbar', true);
        $defaultZoom            = $request->get('defaultZoom', 1);

        unset(
            $validated['style'],
            $validated['headerBackgroundColor'],
            $validated['mapFacadeStyle'],
            $validated['mapBackgroundColor'],
            $validated['showEnemyInfo'],
            $validated['showTitle'],
            $validated['showSidebar'],
            $validated['showHeader'],
            $validated['defaultZoom'],
        );

        $mostRecentSeason = $dungeon->getActiveSeason($seasonService);

        $heatmapActive = Feature::active(Heatmap::class) && ($dungeon->heatmap_enabled || isset($validated['token']));

        $dungeon->trackPageView(Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON_HEATMAP_EMBED);

        return view('dungeon.heatmap.gameversion.embed', array_merge($this->getFilterSettings($mostRecentSeason), [
            'gameVersion'             => $gameVersion,
            'season'                  => $mostRecentSeason,
            'dungeon'                 => $dungeon,
            'floor'                   => $floor,
            'title'                   => __($dungeon->name),
            'mapFacadeStyle'          => $mapFacadeStyle,
            'mapContext'              => $mapContextService->createMapContextDungeonExplore($dungeon, $currentMappingVersion, $mapFacadeStyle),
            'showHeatmapSearch'       => $heatmapActive,
            'seasonWeeklyAffixGroups' => $dungeon->hasMappingVersionWithSeasons() ?
                $seasonAffixGroupService->getWeeklyAffixGroupsSinceStart($mostRecentSeason, GameServerRegion::getUserOrDefaultRegion()) :
                collect(),
            'parameters'   => $validated,
            'defaultZoom'  => $defaultZoom,
            'embedOptions' => [
                'style'                 => $style,
                'headerBackgroundColor' => $headerBackgroundColor,
                'mapBackgroundColor'    => $mapBackgroundColor,
                'show'                  => [
                    'enemyInfo' => (bool)$showEnemyInfo,
                    // Default false - not available
                    'title'              => (bool)$showTitle,
                    'sidebar'            => (bool)$showSidebar,
                    'header'             => (bool)$showHeader,
                    'floorSelection'     => true,
                    'dataSourceSnackbar' => (bool)$showDataSourceSnackbar,
                    // Always available but can be overridden later if there are no floors to select
                ],
            ],
        ]));
    }

    private function getFilterSettings(?Season $season): array
    {
        return [
            'keyLevelMin'           => $season?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'), // @phpstan-ignore nullsafe.neverNull
            'keyLevelMax'           => $season?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'), // @phpstan-ignore nullsafe.neverNull
            'itemLevelMin'          => $season?->item_level_min ?? 0, // @phpstan-ignore nullsafe.neverNull
            'itemLevelMax'          => $season?->item_level_max ?? 0, // @phpstan-ignore nullsafe.neverNull
            'playerDeathsMin'       => 0,
            'playerDeathsMax'       => 99,
            'minSamplesRequiredMin' => 1,
            'minSamplesRequiredMax' => 100,
        ];
    }

    /**
     * Maybe this should go in a policy?
     *
     * @param  Dungeon               $dungeon
     * @param  GameVersion           $gameVersion
     * @param  MappingVersion|null   $currentMappingVersion
     * @return RedirectResponse|null
     */
    private function guardAgainstInvalidAccess(
        GameVersion     $gameVersion,
        Dungeon         $dungeon,
        ?MappingVersion $currentMappingVersion,
        ?Season         $mostRecentSeason = null,
    ): ?RedirectResponse {
        if (
            !$dungeon->active ||
            !$dungeon->heatmap_enabled ||
            $currentMappingVersion === null ||
            $mostRecentSeason === null ||
            !Feature::active(Heatmap::class)
        ) {
            return redirect()->route('dungeon.heatmap.gameversion.select', [
                'gameVersion' => $gameVersion,
            ]);
        }

        return null;
    }
}
