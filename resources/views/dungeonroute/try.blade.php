<?php
/** @var $model \App\Models\DungeonRoute */
?>
@extends('layouts.app', ['custom' => isset($model), 'footer' => !isset($model), 'header' => !isset($model), 'title' => __('Try')])

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
                'no-modifications-warning' => true,
                'virtual-tour' => true
            ],
            'floorSelection' => $floorSelection
        ])

        @include('common.maps.map', [
            'dungeonroute' => $model,
            'edit' => true
        ])
    </div>

    <?php } ?>
@endsection

