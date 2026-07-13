<?php

use App\Logic\MapContext\Map\MapContextBase;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                       $gameVersion
 * @var Dungeon                           $dungeon
 * @var Season                            $season
 * @var Floor                             $floor
 * @var string                            $title
 * @var MapContextBase                    $mapContext
 * @var int                               $keyLevelMin
 * @var int                               $keyLevelMax
 * @var int                               $itemLevelMin
 * @var int                               $itemLevelMax
 * @var int                               $playerDeathsMin
 * @var int                               $playerDeathsMax
 * @var Collection<int, WeeklyAffixGroup> $seasonWeeklyAffixGroups
 * @var Collection<int, Dungeon>          $gameVersionDungeons
 */
?>
@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $title])
@section('linkpreview')
    <?php
    $defaultDescription = sprintf(__('view_dungeonroute.view.linkpreview_default_description_heatmap'), __($dungeon->name))
    ?>
    @include('common.general.linkpreview', [
        'title' => sprintf(__('view_dungeonroute.view.linkpreview_title'), $title),
        'description' => $defaultDescription,
        'image' => $dungeon->getImageUrl(),
    ])
@endsection

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'season' => $season,
            'mappingVersion' => $dungeon->getCurrentMappingVersion(),
            'floor' => $floor,
            'edit' => false,
            'echo' => false,
            'mapContext' => $mapContext,
            'show' => [
                'header' => true,
                'controls' => [
                    'view' => true,
                    'pulls' => false,
                    'heatmapSearch' => true,
                    'enemyInfo' => true,
                ],
            ],
            'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
                $dungeon->key => route('dungeon.heatmap.gameversion.view', [
                    'gameVersion' => $gameVersion,
                    'dungeon' => $dungeon,
                ])
            ])->put('more', route('dungeon.heatmap.gameversion.select', ['gameVersion' => $gameVersion])),
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
            'hiddenMapObjectGroups' => [
                'arrow',
                'brushline',
                'path',
                'killzone',
                'killzonepath',
                'floorunion',
                'floorunionarea',
            ],
        ])
    </div>
@endsection

