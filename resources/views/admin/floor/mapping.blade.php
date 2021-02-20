@extends('layouts.app', ['showAds' => false, 'custom' => true, 'footer' => false, 'header' => false, 'title' => __('Edit') . ' ' . $model->dungeon->name])
@section('header-title')
    {{ $headerTitle }}
@endsection
<?php
/**
 * @var $model \App\Models\Floor
 * @var $mapContext \App\Logic\MapContext\MapContextDungeon
 */
?>

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $model->dungeon,
            'admin' => true,
            'edit' => true,
            'mapContext' => $mapContext,
            'hiddenMapObjectGroups' => [
                'brushline',
                'path',
                'killzone'
            ]
        ])

        @include('common.maps.admineditsidebar', [
            'show' => [
                'sharing' => true,
                'route-publish' => true
            ]
        ])
    </div>

@endsection
