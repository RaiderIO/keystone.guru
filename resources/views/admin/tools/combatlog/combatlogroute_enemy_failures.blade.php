<?php

use App\Logic\MapContext\Map\MapContextDungeonExplore;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;

/**
 * @var Dungeon                  $dungeon
 * @var Floor                    $floor
 * @var MappingVersion           $mappingVersion
 * @var MapContextDungeonExplore $mapContext
 */
?>
@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => __('view_admin.tools.combatlog.route.enemy_failures.title'),
])

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'showAds'               => false,
            'dungeon'               => $dungeon,
            'mappingVersion'        => $mappingVersion,
            'admin'                 => true,
            'edit'                  => false,
            'mapContext'            => $mapContext,
            'floor'                 => $floor,
            'echo'                  => false,
            'hiddenMapObjectGroups' => [
                'arrow',
                'brushline',
                'path',
                'killzone',
                'killzonepath',
                'floorunion',
                'floorunionarea',
                'playerposition',
            ],
            'show'                  => [
                'header'   => true,
                'controls' => [
                    'pulls'                       => false,
                    'view'                        => true,
                    'combatLogRouteEnemyFailures' => true,
                ],
            ],
        ])
    </div>
@endsection
