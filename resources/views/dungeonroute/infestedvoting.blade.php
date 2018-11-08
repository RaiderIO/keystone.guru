@php($custom = isset($dungeon_id))
@extends('layouts.app', ['custom' => $custom, 'footer' => !$custom, 'header' => !$custom, 'title' => __('Infested voting')])

@section('content')
    <?php
    // If the user navigated to /try itself
    if(!isset($dungeon_id)) { ?>
    @include('common.forms.infestedvoting')
    <?php } else {
    $dungeon = \App\Models\Dungeon::findOrFail($dungeon_id);
    $floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
    ?>

    <div class="wrapper">
        @include('common.maps.infestedvotingsidebar', [
            'floorSelection' => $floorSelection
        ])

        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'teeming' => $teeming,
            'edit' => false,
            'showInfestedVoting' => true,
            'enemyVisualType' => 'infested_vote'
        ])
    </div>

    <?php } ?>
@endsection

