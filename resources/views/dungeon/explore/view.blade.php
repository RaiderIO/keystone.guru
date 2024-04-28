<?php

use App\Logic\MapContext\MapContext;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use Carbon\CarbonPeriod;

/**
 * @var Dungeon      $dungeon
 * @var Floor        $floor
 * @var string       $title
 * @var MapContext   $mapContext
 * @var boolean      $showHeatmapSearch
 * @var CarbonPeriod $availableDateRange
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
            'mappingVersion' => $dungeon->currentMappingVersion,
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
                    'availableDateRange' => $availableDateRange,
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

