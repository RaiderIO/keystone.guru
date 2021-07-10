<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */
$dungeon = $dungeonroute->dungeon->load(['expansion', 'floors']);

$sandbox = $dungeonroute->isSandbox();
?>
@extends('layouts.map', ['title' => sprintf(__('Edit %s'), $dungeonroute->title)])

@include('common.general.inline', [
    'path' => 'dungeonroute/edit',
    'dependencies' => ['common/maps/map']
])

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $dungeonroute,
            'edit' => true,
            'sandboxMode' => $sandbox,
            'floorId' => $floor->id,
            'show' => [
                'controls' => [
                    'draw' => true,
                    'pulls' => true,
                    'enemyinfo' => true,
                ],
                'share' => [
                    'link' => !$sandbox,
                    'embed' => !$sandbox,
                    'mdt-export' => true,
                    'publish' => !$sandbox,
                ]
            ],
            'hiddenMapObjectGroups' => [
                'killzonepath'
            ],
        ])
    </div>
@endsection