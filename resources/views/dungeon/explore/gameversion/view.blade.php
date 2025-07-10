<?php

use App\Logic\MapContext\MapContext;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * @var Dungeon                      $dungeon
 * @var Floor                        $floor
 * @var string                       $title
 * @var MapContext                   $mapContext
 * @var boolean                      $showHeatmapSearch
 * @var int                          $keyLevelMin
 * @var int                          $keyLevelMax
 * @var int                          $itemLevelMin
 * @var int                          $itemLevelMax
 * @var int                          $playerDeathsMin
 * @var int                          $playerDeathsMax
 * @var Collection<WeeklyAffixGroup> $seasonWeeklyAffixGroups
 */
?>
@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $title])
@section('linkpreview')
    <?php
    $defaultDescription = sprintf(__('view_dungeonroute.view.linkpreview_default_description_explore'), __($dungeon->name))
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
                    'heatmapSearch' => $showHeatmapSearch,
                    'enemyInfo' => true,
                ],
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
            'hiddenMapObjectGroups' => [
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

