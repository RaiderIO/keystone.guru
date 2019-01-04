@extends('layouts.app', ['custom' => true, 'footer' => false, 'header' => false, 'cookieConsent' => false, 'title' => $model->title, 'noads' => true])
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
            dungeonMap.register('map:mapobjectgroupsfetchsuccess', null, function(){
                dungeonMap.getMapObjectGroupByName('enemy').setVisibility(false);
            });
        });
    </script>
@endsection
@section('content')
    @include('common.maps.map', [
        'dungeon' => $dungeon,
        'dungeonroute' => $model,
        'noads' => true,
        'edit' => false,
        'noUI' => true,
        'defaultZoom' => 1,
        'floorId' => $floorId,
        'showAttribution' => false,
        'hiddenMapObjectGroups' => [
            'enemypatrol',
            'enemypack',
            'mapcomment',
            'dungeonfloorswitchmarker',
        ]
    ])
@endsection

