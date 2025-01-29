<?php

use App\Logic\MapContext\MapContext;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                  $gameVersion
 * @var Dungeon                      $dungeon
 * @var Floor                        $floor
 * @var string                       $title
 * @var MapContext                   $mapContext
 * @var boolean                      $showHeatmapSearch
 * @var int                          $keyLevelMin
 * @var int                          $keyLevelMax
 * @var Collection<WeeklyAffixGroup> $seasonWeeklyAffixGroups
 * @var array                        $embedOptions
 * @var array                        $parameters
 */
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

@include('common.general.inline', ['path' => 'common/maps/embedtopbar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'switchDungeonFloorSelect' => '#map_floor_selection_dropdown',
    'defaultSelectedFloorId' => $floor->id,
    'mdtStringCopyEnabled' => $dungeon->mdt_supported,
]])

@section('content')
    @include(sprintf('dungeon.explore.gameversion.embedheaderstyle.%s', $embedOptions['style']), [
        'gameVersion' => $gameVersion,
        'dungeon' => $dungeon,
        'floor' => $floor,
        'embedOptions' => $embedOptions,
        'parameters' => $parameters,
    ])

    <div class="wrapper embed_wrapper {{ $embedOptions['style'] }}">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'mappingVersion' => $dungeon->currentMappingVersion,
            'mapBackgroundColor' => $embedOptions['mapBackgroundColor'],
            'embed' => true,
//            'embedStyle' => $embedOptions['style'],
            'edit' => false,
            'echo' => false,
            'defaultZoom' => 1,
            'floor' => $floor,
            'showAttribution' => false,
            'parameters' => $parameters,
            'mapContext' => $mapContext,
            'hiddenMapObjectGroups' => [
                'brushline',
                'enemypack',
                'floorunion',
                'floorunionarea',
                'killzone',
                'killzonepath',
                'mountablearea',
                'path',
            ],
            'controlOptions' => [
                'heatmapSearch' => [
                    'keyLevelMin' => $keyLevelMin,
                    'keyLevelMax' => $keyLevelMax,
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
                    'heatmapSearch' => true,
                    'heatmapSearchDefaultState' => false,
                ],
            ],
        ])
    </div>
@endsection

