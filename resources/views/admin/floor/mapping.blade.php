@extends('layouts.map', ['showAds' => false, 'custom' => true, 'footer' => false, 'header' => false, 'title' => __('Edit') . ' ' . $floor->dungeon->name])
@section('header-title')
    {{ $headerTitle }}
@endsection
<?php
/**
 * @var $floor \App\Models\Floor
 * @var $mapContext \App\Logic\MapContext\MapContextDungeon
 */
?>

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'showAds' => false,
            'dungeon' => $floor->dungeon,
            'admin' => true,
            'edit' => true,
            'mapContext' => $mapContext,
            'floorId' => $floor->id,
            'hiddenMapObjectGroups' => [
                'brushline',
                'path',
                'killzone',
                'killzonepath'
            ]
        ])
    </div>

@endsection
