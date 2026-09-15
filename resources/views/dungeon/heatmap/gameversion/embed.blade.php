<?php

use App\Logic\MapContext\Map\MapContextBase;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                       $gameVersion
 * @var Dungeon                           $dungeon
 * @var Floor                             $floor
 * @var string                            $title
 * @var MapContextBase                    $mapContext
 * @var boolean                           $showHeatmapSearch
 * @var int                               $keyLevelMin
 * @var int                               $keyLevelMax
 * @var int                               $itemLevelMin
 * @var int                               $itemLevelMax
 * @var int                               $playerDeathsMin
 * @var int                               $playerDeathsMax
 * @var Collection<int, WeeklyAffixGroup> $seasonWeeklyAffixGroups
 * @var array<string, mixed>              $embedOptions
 * @var array<string, mixed>              $parameters
 * @var float                             $defaultZoom
 */

$showHeader = !isset($embedOptions['show']['header']) || $embedOptions['show']['header'];
?>
@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => $title,
    'bodyClass' => 'overflow-hidden',
    'cookieConsent' => false,
])

@include('common.general.inline', ['path' => 'dungeon/heatmap/gameversion/embed', 'options' => [
    'dependencies' => ['common/maps/map'],
]])

@include('common.general.inline', ['path' => 'common/maps/embedtopbar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'switchDungeonFloorSelect' => '#map_floor_selection_dropdown',
    'defaultSelectedFloorId' => $floor->id,
    'mdtStringCopyEnabled' => false,
]])

@section('content')
    @if($showHeader)
        @include(sprintf('dungeon.heatmap.gameversion.embedheaderstyle.%s', $embedOptions['style']), [
            'gameVersion' => $gameVersion,
            'dungeon' => $dungeon,
            'floor' => $floor,
            'embedOptions' => $embedOptions,
            'parameters' => $parameters,
        ])
    @endif

    <div class="wrapper embed_wrapper {{ $showHeader ? $embedOptions['style'] : '' }}">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'mappingVersion' => $dungeon->getCurrentMappingVersion($gameVersion),
            'mapBackgroundColor' => $embedOptions['mapBackgroundColor'],
            'embed' => true,
//            'embedStyle' => $embedOptions['style'],
            'edit' => false,
            'echo' => false,
            'defaultZoom' => $defaultZoom,
            'floor' => $floor,
            'showAttribution' => false,
            'parameters' => $parameters,
            'mapContext' => $mapContext,
            'hiddenMapObjectGroups' => [
                'arrow',
                'brushline',
                'floorunion',
                'floorunionarea',
                'killzone',
                'killzonepath',
                'mountablearea',
                'path',
                'playerposition',
            ],
            'controlOptions' => [
                'heatmapSearch' => [
                    'keyLevelMin' => $keyLevelMin,
                    'keyLevelMax' => $keyLevelMax,
                    'itemLevelMin' => $itemLevelMin,
                    'itemLevelMax' => $itemLevelMax,
                    'playerDeathsMin' => $playerDeathsMin,
                    'playerDeathsMax' => $playerDeathsMax,
                    'seasonWeeklyAffixGroups' => $seasonWeeklyAffixGroups,
                ],
            ],
            'show' => [
                'header' => false,
                'controls' => [
                    'view' => false,
                    'pulls' => false,
                    'enemyInfo' => $embedOptions['show']['enemyInfo'],
                    'title' => $embedOptions['show']['enemyInfo'],
                    'heatmapSearch' => $showHeatmapSearch,
                    'heatmapSearchDefaultState' => false,
                    'heatmapSearchSidebar' => $embedOptions['show']['sidebar'],
                    'heatmapSearchShowDataSourceSnackbar' => $embedOptions['show']['dataSourceSnackbar'],
                ],
            ],
        ])
    </div>
@endsection

