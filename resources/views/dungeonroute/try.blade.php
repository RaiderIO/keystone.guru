@php($custom = isset($dungeon_id))
@extends('layouts.app', ['custom' => $custom, 'footer' => !$custom, 'headerFloat' => $custom])
@section('header-title', $headerTitle)

@section('content')
    <?php
    // If the user navigated to /try itself
    if(!isset($dungeon_id)) { ?>
    @include('common.forms.try')
    <?php } else { ?>

    <div class="wrapper">
        @include('common.maps.sidebar', [
            'show' => [
                'no-modifications-warning' => true,
                'virtual-tour' => true
            ]
        ])

        @include('common.maps.map', [
            'dungeon' => \App\Models\Dungeon::findOrFail($dungeon_id),
            'edit' => true
        ])
    </div>

    <?php } ?>
@endsection

