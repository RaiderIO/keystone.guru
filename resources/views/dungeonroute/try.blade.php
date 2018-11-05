@php($custom = isset($dungeon_id))
@extends('layouts.app', ['custom' => $custom, 'footer' => !$custom, 'header' => !$custom])
@section('header-title', $headerTitle)

@section('content')
    <?php
    // If the user navigated to /try itself
    if(!isset($dungeon_id)) { ?>
    @include('common.forms.try')
    <?php } else {
    $dungeon = \App\Models\Dungeon::findOrFail($dungeon_id);
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
            'dungeon' => $dungeon,
            'edit' => true
        ])
    </div>

    <?php } ?>
@endsection

