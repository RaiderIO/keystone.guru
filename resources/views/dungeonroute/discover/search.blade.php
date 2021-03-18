@extends('layouts.sitepage', ['rootClass' => 'discover', 'title' => __('Search routes')])

@section('header-title')
    {{ __('Search routes') }}
@endsection
<?php
/**
 */
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/search',
        'options' =>  [
            'min' => config('keystoneguru.levels.min'),
            'max' => config('keystoneguru.levels.max'),
        ]
])

@section('content')
    <div class="discover_panel">
        @include('common.dungeon.grid', ['names' => true])
    </div>
    <div class="row">
        <div class="col-lg-3">
            @component('common.dungeonroute.search.filter', ['key' => 'title', 'text' => __('Title')])
                {!! Form::text('title', request('title'), ['id' => 'title', 'class' => 'form-control', 'placeholder' => __('Filter by title'), 'autocomplete' => 'off']) !!}
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'complexity', 'text' => __('Difficulty')])
                <input id="difficulty" type="checkbox"
                       checked="checked"
                       data-toggle="toggle" data-width="100%" data-height="20px"
                       data-onstyle="primary" data-offstyle="primary"
                       data-on="{{ __('Simple') }}" data-off="{{ __('Complex') }}">
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'level', 'text' => __('Key level')])
                <input id="level" type="text" name="level" value="{{ old('level') }}" style="display: none;" />
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'affix', 'text' => __('Affix')])
                Affix
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'affixes', 'text' => __('Affixes')])
                Affixes
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'enemy_forces', 'text' => __('Enemy forces')])
                <input id="enemy_forces" type="checkbox"
                       checked="checked"
                       data-toggle="toggle" data-width="100%" data-height="20px"
                       data-onstyle="success" data-offstyle="warning"
                       data-on="{{ __('Complete') }}" data-off="{{ __('Not complete') }}">
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'rating', 'text' => __('Rating')])
                <input id="rating" type="text" name="level" value="{{ old('rating') }}" style="display: none;" />
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'user', 'text' => __('User'), 'expanded' => false])
                {!! Form::text('user', request('user'), ['id' => 'user', 'class' => 'form-control', 'placeholder' => __('Filter by user'), 'autocomplete' => 'off']) !!}
            @endcomponent
        </div>
        <div class="col-lg-9">
            <div id="route_list">

            </div>
            <div id="route_list_overlay" style="display: none;">

            </div>
        </div>
    </div>
@endsection