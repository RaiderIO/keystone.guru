@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'cookieConsent' => false, 'title' => $model->title, 'showAds' => false, 'analytics' => false])
<?php
/** @var \App\Models\DungeonRoute $model */
/** @var int $floorId */

/** @var \App\Models\Dungeon $dungeon */
$dungeon = \App\Models\Dungeon::findOrFail($model->dungeon_id);
$dungeon->load('floors');
?>
@section('scripts')
    @parent

    <script>
        $(function(){
            // We need to fetch the enemies so the killzone polygon knows what to draw, but we don't want to display
            // the enemies themselves so hide those for displaying.
            dungeonMap.register('map:mapobjectgroupsloaded', null, function(){
                dungeonMap.mapObjectGroupManager.getByName('enemy').setVisibility(false);
            });
        });
    </script>
@endsection
@section('content')
    @include('common.maps.map', [
        'dungeonroute' => $model,
        'showAds' => false,
        'edit' => false,
        'noUI' => true,
        'defaultZoom' => 1,
        'floorId' => $floorId,
        'showAttribution' => false,
        'zoomToContents' => true,
        'hiddenMapObjectGroups' => [
            'enemypatrol',
            'enemypack',
            'dungeonfloorswitchmarker',
        ]
    ])
@endsection

