<?php
/** @var $model \App\Models\DungeonRoute */
?>
@extends('layouts.app', [
    'custom' => isset($model),
    'footer' => !isset($model),
    'header' => !isset($model),
    'title' => __('Try'),
    'loginParams' => isset($model) ? ['redirect' => route('dungeonroute.try', ['dungeonroute' => $model->public_key])] : [],
    'registerParams' => isset($model) ? ['redirect' => route('dungeonroute.try', ['dungeonroute' => $model->public_key])] : []
])

@section('content')
    <?php
    // If the user navigated to /try itself
    if(!isset($model)) { ?>
    @include('common.forms.try')
    <?php } else {
    $dungeon = $model->dungeon;
    $floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
    ?>

    <div class="wrapper">
        @include('common.maps.editsidebar', [
            'show' => [
                'virtual-tour' => true,
                'tryout' => true
            ],
            'floorSelection' => $floorSelection
        ])

        @include('common.maps.map', [
            'dungeonroute' => $model,
            'edit' => true,
            'tryMode' => true
        ])
    </div>

    <?php } ?>
@endsection

