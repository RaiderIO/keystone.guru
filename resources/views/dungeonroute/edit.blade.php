<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */
$dungeon = $dungeonroute->dungeon->load(['expansion', 'floors']);

$sandbox = $dungeonroute->isSandbox();
?>

@extends('layouts.map', ['title' => sprintf(__('views/dungeonroute.edit.title'), $dungeonroute->title)])

@include('common.general.inline', [
    'path' => 'dungeonroute/edit',
    'dependencies' => ['common/maps/map'],
    'options' => [
        'dungeonroute' => $dungeonroute,
        'levelMin' => config('keystoneguru.levels.min'),
        'levelMax' => config('keystoneguru.levels.max'),
    ]
])

@section('linkpreview')
    @include('common.general.linkpreview', [
        'title' => sprintf(__('views/dungeonroute.edit.linkpreview_title'), $dungeonroute->title),
        'description' => !empty($dungeonroute->description) ?
            $dungeonroute->description :
            sprintf(__('views/dungeonroute.edit.linkpreview_default_description'), __($dungeonroute->dungeon->name), $dungeonroute->author->name),
            'image' => $dungeonroute->dungeon->getImageUrl()
    ])
@endsection

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $dungeonroute,
            'edit' => true,
            'sandboxMode' => $sandbox,
            'floorId' => $floor->id,
            'show' => [
                'header' => true,
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
            'hiddenMapObjectGroups' => [],
        ])
    </div>
@endsection
