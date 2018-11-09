@extends('layouts.app', ['noads' => true, 'custom' => true, 'footer' => false, 'header' => false, 'title' => __('Edit') . ' ' . $dungeon->name])
@section('header-title')
    {{ $headerTitle }}
@endsection
<?php
/**
 * @var $model \App\Models\Floor
 * @var $dungeon \App\Models\Dungeon
 * @var $floors \Illuminate\Support\Collection
 */
?>

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'admin' => true,
            'edit' => true,
            'dungeon' => $dungeon,
            'npcs' => $npcs,
            'selectedFloorId' => $model->id
        ])

        @include('common.maps.admineditsidebar', [
            'show' => [
                'shareable-link' => true,
                'route-settings' => true,
                'route-publish' => true
            ]
        ])
    </div>

@endsection
