@php use App\Logic\MapContext\MapContextMappingVersion;use App\Models\Floor\Floor;use App\Models\Mapping\MappingVersion; @endphp
<?php
/**
 * @var Floor                    $floor
 * @var MapContextMappingVersion $mapContext
 * @var MappingVersion           $mappingVersion
 */
?>

@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => sprintf(__('view_admin.floor.mapping.title'), __($floor->dungeon->name)),
])
@section('header-title')
    {{ sprintf(__('view_admin.floor.mapping.header'), __($floor->dungeon->name)) }}
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
                'killzonepath',
            ],
            'show' => [
                'header' => true,
                'controls' => [
                    'pulls' => false,
                    'draw' => true,
                    'enemyInfo' => true,
                ],
            ],
        ])
    </div>

@endsection
