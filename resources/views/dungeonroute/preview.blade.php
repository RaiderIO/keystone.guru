<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute $dungeonroute
 * @var int          $floorId
 * @var float        $defaultZoom
 * @var string       $mapFacadeStyle
 * @var array        $parameters
 */
?>

@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'cookieConsent' => false,
    'title' => $dungeonroute->title,
    'analytics' => false,
])
@section('scripts')
    @parent

    <script>
        $(function () {
            // We need to fetch the enemies so the killzone polygon knows what to draw, but we don't want to display
            // the enemies themselves so hide those for displaying.
            dungeonMap.register('map:mapobjectgroupsloaded', null, function () {
                dungeonMap.mapObjectGroupManager.getByName('enemy').setVisibility(false);
            });
        });
    </script>
@endsection
@section('content')
    @include('common.maps.map', [
        'dungeon' => $dungeonroute->dungeon,
        'mappingVersion' => $dungeonroute->mappingVersion,
        'dungeonroute' => $dungeonroute,
        'showAds' => false,
        'edit' => false,
        'echo' => false,
        'noUI' => true,
        'defaultZoom' => $defaultZoom,
        'mapFacadeStyle' => $mapFacadeStyle,
        'floorId' => $floorId,
        'showAttribution' => false,
        'zoomToContents' => false,
        'parameters' => $parameters,
        'hiddenMapObjectGroups' => [
            'enemypatrol',
            'enemypack',
            'mountablearea',
            'floorunion',
            'floorunionarea',
        ],
    ])
@endsection

