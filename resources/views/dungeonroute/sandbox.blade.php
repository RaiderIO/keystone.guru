<?php
/** @var $model \App\Models\DungeonRoute */
?>
@extends('layouts.app', [
    'custom' => isset($model),
    'footer' => !isset($model),
    'header' => !isset($model),
    'title' => __('Sandbox'),
    'loginParams' => isset($model) ? ['redirect' => route('dungeonroute.sandbox', ['dungeonroute' => $model->public_key])] : [],
    'registerParams' => isset($model) ? ['redirect' => route('dungeonroute.sandbox', ['dungeonroute' => $model->public_key])] : []
])

@section('content')
    <?php
    // If the user navigated to /try itself
    if(!isset($model)) { ?>
    @include('common.forms.sandbox')
    <?php } else {
    $dungeon = $model->dungeon;
    $floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
    ?>

    <div class="wrapper">
        @include('common.maps.editsidebar', [
            'show' => [
                'virtual-tour' => true,
                'sandbox' => true,
                'draw-settings' => true
            ],
            'floorId' => $floor->id,
            'dungeon' => $dungeon,
            'floorSelection' => $floorSelection
        ])

        @include('common.maps.map', [
            'dungeonroute' => $model,
            'edit' => true,
            'sandboxMode' => true,
            'floorId' => $floor->id
        ])

        @include('common.maps.killzonessidebar', [
            'edit' => true
        ])
    </div>

    <?php } ?>
@endsection

