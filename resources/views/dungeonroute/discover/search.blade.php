@extends('layouts.sitepage', ['rootClass' => 'discover', 'title' => __('views/dungeonroute.discover.search.page_title')])

@inject('seasonService', 'App\Service\Season\SeasonService')

@section('header-title')
    {{ __('views/dungeonroute.discover.search.header') }}
@endsection
<?php
/** @var $seasonService \App\Service\Season\SeasonService */
$affixgroups = $seasonService->getCurrentSeason()->affixgroups()->with('affixes')->get();
$featuredAffixes = $seasonService->getCurrentSeason()->getFeaturedAffixes();
// Divide in 2 parts
$featuredAffixes = $featuredAffixes->chunk(ceil($featuredAffixes->count() / 3));
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/search', 'options' =>  [
        'levelMin' => config('keystoneguru.levels.min'),
        'levelMax' => config('keystoneguru.levels.max'),
        'limit' => config('keystoneguru.discover.limits.search')
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
    <div class="row mb-2">
        <div class="col-xl-3">

        </div>
        <div id="route_list_current_filters" class="col-xl-9">

        </div>
    </div>
    <div class="row">
        <div class="col-xl-3">
            @component('common.dungeonroute.search.filter', ['key' => 'title', 'text' => __('.views/dungeonroute.discover.search.title')])
                {!! Form::text('title', request('title'), ['id' => 'title', 'class' => 'form-control', 'placeholder' => __('views/dungeonroute.discover.search.title_placeholder'), 'autocomplete' => 'off']) !!}
            @endcomponent
            {{--            @component('common.dungeonroute.search.filter', ['key' => 'complexity', 'text' => __('Difficulty')])--}}
            {{--                <input id="difficulty" type="checkbox"--}}
            {{--                       checked="checked"--}}
            {{--                       data-toggle="toggle" data-width="100%" data-height="20px"--}}
            {{--                       data-onstyle="primary" data-offstyle="primary"--}}
            {{--                       data-on="{{ __('Simple') }}" data-off="{{ __('Complex') }}">--}}
            {{--            @endcomponent--}}
            @component('common.dungeonroute.search.filter', ['key' => 'level', 'text' => __('views/dungeonroute.discover.search.key_level')])
                <input id="level" type="text" name="level" value="{{ old('level') }}" style="display: none;"/>
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'affixes', 'text' => __('affixes.views/dungeonroute.discover.search.affixes')])
                <div class="row">
                    <div class="col">
                        {!! Form::select('filter_affixes[]', $affixgroups->pluck('text', 'id'), [],
                            ['id' => 'filter_affixes',
                            'class' => 'form-control affixselect selectpicker',
                            'multiple' => 'multiple',
                            'title' => __('views/dungeonroute.discover.search.affixes_title'),
                            'data-selected-text-format' => 'count > 1',
                            'data-count-selected-text' => __('views/dungeonroute.discover.search.affixes_selected')]) !!}
                    </div>
                </div>
                @foreach($featuredAffixes as $affixRow)
                    <div class="row mt-2 pl-2">
                        @foreach($affixRow as $affix)
                            <div class="col px-xl-1">
                                <div class="select_icon class_icon affix_icon_{{ strtolower($affix->key) }} selectable"
                                     data-toggle="tooltip" data-id="{{ $affix->id }}"
                                     title="{{ __($affix->description) }}"
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
                       data-on="{{ __('views/dungeonroute.discover.search.enemy_forces_complete') }}"
                       data-off="{{ __('views/dungeonroute.discover.search.enemy_forces_incomplete') }}">
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'rating', 'text' => __('views/dungeonroute.discover.search.rating)])
                <input id="rating" type="text" name="level" value="{{ old('rating') }}" style="display: none;"/>
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'user', 'text' => __('.views/dungeonroute.discover.search.user'), 'expanded' => false])
                {!! Form::text('user', request('user'), ['id' => 'user', 'class' => 'form-control', 'placeholder' => __('views/dungeonroute.discover.search.user_placeholder'), 'autocomplete' => 'off']) !!}
            @endcomponent
        </div>
        <div class="col-xl-9">
            <div id="route_list">

            </div>
            <div id="route_list_overlay" style="display: none;">

            </div>
        </div>
    </div>
    <div id="route_list_load_more" class="row">
        <div class="col">
            &nbsp;
        </div>
    </div>
@endsection