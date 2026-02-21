<?php

use App\Logic\MapContext\Map\MapContextBase;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Illuminate\Support\Collection;

/**
 * @var Dungeon        $dungeon
 * @var Floor          $floor
 * @var MapContextBase $mapContext
 */
?>
@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $title])
@section('linkpreview')
    <?php
    $defaultDescription = __('view_dungeon.dungeonroute.search.gameversion.dungeon.linkpreview_default_description_search', [
        'dungeon' => __($dungeon->name)
    ]);
    ?>
    @include('common.general.linkpreview', [
        'title' => __('view_dungeon.dungeonroute.search.gameversion.dungeon.linkpreview_title', ['title' => $title]),
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
            'headerTitle' => __('view_dungeon.dungeonroute.search.gameversion.dungeon.title', ['dungeon' => __($dungeon->name)]),
            'show' => [
                'header' => true,
                'controls' => [
                    'view' => true,
                    'pulls' => false,
                    'heatmapSearch' => false,
                    'enemyInfo' => true,
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

