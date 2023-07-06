<?php
/**
 * @var $floor \App\Models\Floor
 * @var $mapContext \App\Logic\MapContext\MapContextDungeon
 * @var $mappingVersion \App\Models\Mapping\MappingVersion
 */
?>

@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => sprintf(__('views/admin.floor.mapping.title'), __($floor->dungeon->name))
])
@section('header-title')
    {{ sprintf(__('views/admin.floor.mapping.header'), __($floor->dungeon->name)) }}
@endsection

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'showAds' => false,
            'dungeon' => $floor->dungeon,
            'mappingVersion' => $mappingVersion,
            'admin' => true,
            'edit' => true,
            'mapContext' => $mapContext,
            'floorId' => $floor->id,
            'hiddenMapObjectGroups' => [
                'brushline',
                'path',
                'killzone',
                'killzonepath'
            ],
            'show' => [
                'header' => true,
                'controls' => [
                    'draw' => true,
                    'enemyinfo' => true,
                ],
            ],
        ])
    </div>

@endsection
