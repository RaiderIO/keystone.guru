@extends('layouts.sitepage', ['rootClass' => 'discover', 'title' => __('Search routes')])

@inject('seasonService', 'App\Service\Season\SeasonService')

@section('header-title')
    {{ __('Search routes') }}
@endsection
<?php
/** @var $seasonService \App\Service\Season\SeasonService */
$affixgroups = $seasonService->getCurrentSeason()->affixgroups()->with('affixes')->get();
$featuredAffixes = $seasonService->getCurrentSeason()->getFeaturedAffixes();
// Divide in 2 parts
$featuredAffixes = $featuredAffixes->chunk(ceil($featuredAffixes->count() / 3));
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/search',
        'options' =>  [
            'levelMin' => config('keystoneguru.levels.min'),
            'levelMax' => config('keystoneguru.levels.max'),
        ]
])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', [
        'id' => 'filter_affixes',
        'affixgroups' => $affixgroups
    ])
@endsection

@section('content')
    <div class="discover_panel">
        @include('common.dungeon.grid', ['names' => true, 'selectable' => true])
    </div>
    <div class="row">
        <div class="col-xl-3">
            @component('common.dungeonroute.search.filter', ['key' => 'title', 'text' => __('Title')])
                {!! Form::text('title', request('title'), ['id' => 'title', 'class' => 'form-control', 'placeholder' => __('Filter by title'), 'autocomplete' => 'off']) !!}
            @endcomponent
{{--            @component('common.dungeonroute.search.filter', ['key' => 'complexity', 'text' => __('Difficulty')])--}}
{{--                <input id="difficulty" type="checkbox"--}}
{{--                       checked="checked"--}}
{{--                       data-toggle="toggle" data-width="100%" data-height="20px"--}}
{{--                       data-onstyle="primary" data-offstyle="primary"--}}
{{--                       data-on="{{ __('Simple') }}" data-off="{{ __('Complex') }}">--}}
{{--            @endcomponent--}}
            @component('common.dungeonroute.search.filter', ['key' => 'level', 'text' => __('Key level')])
                <input id="level" type="text" name="level" value="{{ old('level') }}" style="display: none;"/>
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'affixes', 'text' => __('Affixes')])
                <div class="row">
                    <div class="col">
                        {!! Form::select('filter_affixes[]', $affixgroups->pluck('text', 'id'), [],
                            ['id' => 'filter_affixes',
                            'class' => 'form-control affixselect selectpicker',
                            'multiple' => 'multiple',
                            'title' => __('Select affixes'),
                            'data-selected-text-format' => 'count > 1',
                            'data-count-selected-text' => __('{0} affixes selected')]) !!}
                    </div>
                </div>
                @foreach($featuredAffixes as $affixRow)
                    <div class="row mt-2 pl-2">
                        @foreach($affixRow as $affix)
                            <div class="col px-xl-1">
                                <div class="select_icon class_icon affix_icon_{{ strtolower($affix->name) }} selectable"
                                     data-toggle="tooltip" data-id="{{ $affix->id }}"
                                     title="{{ $affix->description }}"
                                     style="height: 24px;">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'enemy_forces', 'text' => __('Enemy forces')])
                <input id="enemy_forces" type="checkbox"
                       checked="checked"
                       data-toggle="toggle" data-width="100%" data-height="20px"
                       data-onstyle="success" data-offstyle="warning"
                       data-on="{{ __('Complete') }}" data-off="{{ __('Not complete') }}">
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'rating', 'text' => __('Rating')])
                <input id="rating" type="text" name="level" value="{{ old('rating') }}" style="display: none;"/>
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'user', 'text' => __('User'), 'expanded' => false])
                {!! Form::text('user', request('user'), ['id' => 'user', 'class' => 'form-control', 'placeholder' => __('Filter by user'), 'autocomplete' => 'off']) !!}
            @endcomponent
        </div>
        <div class="col-xl-9">
            <div id="route_list">

            </div>
            <div id="route_list_overlay" style="display: none;">

            </div>
        </div>
    </div>
@endsection