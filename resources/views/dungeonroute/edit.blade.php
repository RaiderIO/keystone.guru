@extends('layouts.app', ['showAds' => false, 'custom' => true, 'footer' => false, 'header' => false, 'title' => __('Edit') . ' ' . $model->title])
<?php
/** @var $model \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */
$dungeon = \App\Models\Dungeon::findOrFail($model->dungeon_id)->load(['expansion', 'floors']);
?>
@include('common.general.inline', [
    'path' => 'dungeonroute/edit',
    'dependencies' => ['common/maps/map']
])

@section('content')
    <div class="wrapper">
        @include('common.maps.editsidebar', [
            'dungeon' => $dungeon,
            'floorId' => $floor->id,
            'show' => [
                'shareable-link' => true,
                'draw-settings' => true,
                'route-settings' => true,
                'route-publish' => true,
                'tags' => false, // Temporarily disabled
            ]
        ])

        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $model,
            'edit' => true,
            'test' => 'test',
            'floorId' => $floor->id
        ])

        @include('common.maps.killzonessidebar', [
            'edit' => true
        ])
    </div>
@endsection