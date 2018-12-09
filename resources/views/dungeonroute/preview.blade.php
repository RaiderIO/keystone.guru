@extends('layouts.app', ['custom' => true, 'footer' => false, 'header' => false, 'cookieConsent' => false, 'title' => $model->title])
<?php
/** @var \App\Models\DungeonRoute $model */
/** @var int $floorId */

/** @var \App\Models\Dungeon $dungeon */
$dungeon = \App\Models\Dungeon::findOrFail($model->dungeon_id);
$dungeon->load('floors');
?>
@section('content')
    @include('common.maps.map', [
        'dungeon' => $dungeon,
        'dungeonroute' => $model,
        'edit' => false,
        'noUI' => true,
        'defaultZoom' => 1,
        'floorId' => $floorId,
        'showAttribution' => false,
        'hiddenMapObjectGroups' => [
            'enemy',
            'enemypatrol',
            'enemypack',
            'mapcomment',
            'dungeonfloorswitchmarker',
        ]
    ])
@endsection

