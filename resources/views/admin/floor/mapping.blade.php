<?php

use App\Logic\MapContext\Map\MapContextMappingVersion;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @var Floor                    $floor
 * @var MapContextMappingVersion $mapContext
 * @var MappingVersion           $mappingVersion
 * @var Collection<int, Dungeon> $gameVersionDungeons
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
            ],
            'show' => [
                'header' => true,
                'controls' => [
                    'pulls' => false,
                    'draw' => true,
                    'enemyInfo' => true,
                ],
            ],
            // Navigating to a different dungeon from here should land on ITS newest mapping version,
            // not replay the generic site-wide dungeon-context redirect (which just re-shows the
            // referring URL for the newly selected dungeon, i.e. this exact mapping version - #4218).
            'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn(Dungeon $dungeon) => [
                $dungeon->key => route('admin.floor.edit.mapping', [
                    'dungeon' => $dungeon,
                    'floor' => $dungeon->floors()->first(),
                    'mapping_version' => $dungeon->getCurrentMappingVersion(),
                ]),
            ]),
        ])
    </div>

@endsection
