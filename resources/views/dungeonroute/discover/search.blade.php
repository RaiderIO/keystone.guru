@extends('layouts.sitepage', ['rootClass' => 'discover', 'title' => __('Search routes')])

@section('header-title')
    {{ __('Search routes') }}
@endsection
<?php
/**
 */
?>

@section('content')
    <div class="discover_panel">
        @include('common.dungeon.grid', ['names' => true])
    </div>
    <div class="row">
        <div class="col-lg-3">
            @component('common.dungeonroute.search.filter', ['key' => 'title', 'text' => __('Title')])
                Title
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'key_level', 'text' => __('Key level')])
                Key level
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'enemy_forces', 'text' => __('Enemy forces')])
                Enemy forces reached?
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'affix', 'text' => __('Affix')])
                Affix
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'complexity', 'text' => __('Complexity')])
                Complex/not complex
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'rating', 'text' => __('Rating')])
                Rating
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'user', 'text' => __('User'), 'expanded' => false])
                User
            @endcomponent
        </div>
        <div class="col-lg-9">
            Route list
        </div>
    </div>
@endsection