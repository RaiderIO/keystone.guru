<?php
/** @var $model \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */
$dungeon = $model->dungeon->load(['expansion', 'floors']);

$sandbox = $model->isSandbox();
?>
@extends('layouts.map', ['title' => sprintf(__('Edit %s'), $model->title)])

@include('common.general.inline', [
    'path' => 'dungeonroute/edit',
    'dependencies' => ['common/maps/map']
])

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $model,
            'edit' => true,
            'sandboxMode' => $sandbox,
            'floorId' => $floor->id,
            'show' => [
                'share' => [
                    'link' => !$sandbox,
                    'embed' => !$sandbox,
                    'mdt-export' => true,
                    'publish' => !$sandbox,
                ]
            ]
        ])

        @include('common.maps.killzonessidebar', [
            'edit' => true,
            'show' => [
                'route-settings' => !$sandbox,
            ],
        ])
    </div>
@endsection