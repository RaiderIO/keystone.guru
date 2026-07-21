<?php

use App\Logic\MapContext\Map\MapContextMappingVersion;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;

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

@php(ob_start())
<a href="{{ route('admin.floor.edit', ['dungeon' => $floor->dungeon, 'floor' => $floor]) }}">
    {{ sprintf(__('view_admin.floor.mapping.header_title'), __($floor->dungeon->name), $mappingVersion->version) }}
</a>
@php($headerTitle = ob_get_clean())

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'showAds' => false,
            'dungeon' => $floor->dungeon,
            'mappingVersion' => $mappingVersion,
            'admin' => true,
            'edit' => true,
            'mapContext' => $mapContext,
            'headerTitle' => $headerTitle,
            // Always show split floors for admin mapping
            'mapFacadeStyle' => User::MAP_FACADE_STYLE_SPLIT_FLOORS,
            'floor' => $floor,
            'hiddenMapObjectGroups' => [
                'arrow',
                'brushline',
                'path',
                'killzone',
                'killzonepath',
                'playerposition'
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
