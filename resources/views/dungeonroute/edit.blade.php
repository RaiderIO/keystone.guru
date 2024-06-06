<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;

/**
 * @var DungeonRoute $dungeonroute
 * @var Floor        $floor
 * @var int          $keyLevelMin
 * @var int          $keyLevelMax
 */

$dungeon = $dungeonroute->dungeon->load(['expansion', 'floors']);

$sandbox = $dungeonroute->isSandbox();
?>
@extends('layouts.map', ['title' => sprintf(__('view_dungeonroute.edit.title'), $dungeonroute->title)])

@include('common.general.inline', [
    'path' => 'dungeonroute/edit',
    'dependencies' => ['common/maps/map'],
    'options' => [
        'dungeonroute' => $dungeonroute,
        'levelMin' => $keyLevelMin,
        'levelMax' => $keyLevelMax,
    ],
])

@section('linkpreview')
    @include('common.general.linkpreview', [
        'title' => sprintf(__('view_dungeonroute.edit.linkpreview_title'), $dungeonroute->title),
        'description' => !empty($dungeonroute->description) ?
            $dungeonroute->description :
            ($dungeonroute->isSandbox() ?
            sprintf(__('view_dungeonroute.edit.linkpreview_default_description_sandbox'), __($dungeonroute->dungeon->name)) :
            sprintf(__('view_dungeonroute.edit.linkpreview_default_description'), __($dungeonroute->dungeon->name), $dungeonroute->author->name)),
            'image' => $dungeonroute->dungeon->getImageUrl(),
    ])
@endsection

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'mappingVersion' => $dungeonroute->mappingVersion,
            'dungeonroute' => $dungeonroute,
            'edit' => true,
            'sandboxMode' => $sandbox,
            'floorId' => $floor->id,
            'show' => [
                'header' => true,
                'controls' => [
                    'draw' => true,
                    'pulls' => true,
                    'enemyInfo' => true,
                ],
                'share' => [
                    'link' => !$sandbox,
                    'embed' => !$sandbox,
                    'mdt-export' => $dungeon->mdt_supported,
                    'publish' => !$sandbox,
                ],
            ],
            'hiddenMapObjectGroups' => [
                'floorunion',
                'floorunionarea',
            ],
        ])
    </div>
@endsection
