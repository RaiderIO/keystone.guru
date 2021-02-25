<?php
/** @var $model \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */
$dungeon = $model->dungeon->load(['expansion', 'floors']);

$sandbox = $model->isSandbox();
//    $dungeon->load(['expansion']);
$floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;

?>
@extends('layouts.map', ['title' => sprintf(__('Edit %s'), $model->title)])

@include('common.general.inline', [
    'path' => 'dungeonroute/edit',
    'dependencies' => ['common/maps/map']
])

@section('content')
    <div class="wrapper">
        @include('common.maps.editsidebar', [
            'dungeon' => $dungeon,
            'floorId' => $floor->id,
            'floorSelection' => $floorSelection,
            'show' => [
                'virtual-tour' => $sandbox,
                'sandbox' => $sandbox,
                'sharing' => true,
                'shareable-link' => !$sandbox,
                'embedable-link' => !$sandbox,
                'export-mdt-string' => true,

                'route-publish' => !$sandbox,
            ]
        ])

        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $model,
            'edit' => true,
            'sandboxMode' => $sandbox,
            'floorId' => $floor->id
        ])

        @include('common.maps.killzonessidebar', [
            'edit' => true,
            'show' => [
                'route-settings' => !$sandbox,
            ],
        ])
    </div>
@endsection