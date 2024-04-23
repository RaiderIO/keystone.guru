@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'cookieConsent' => false,
    'title' => $dungeonroute->title,
    'analytics' => false,
])
<?php
/**
 * @var \App\Models\DungeonRoute\DungeonRoute $dungeonroute
 * @var int                                   $floorId
 * @var float                                 $defaultZoom
 * @var string                                $mapFacadeStyle
 */

/** @var \App\Models\Dungeon $dungeon */
$dungeon = \App\Models\Dungeon::findOrFail($dungeonroute->dungeon_id);
$dungeon->load('floors');
?>
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
        'hiddenMapObjectGroups' => [
            'enemypatrol',
            'enemypack',
            'mountablearea',
            'floorunion',
            'floorunionarea',
        ],
    ])
@endsection

