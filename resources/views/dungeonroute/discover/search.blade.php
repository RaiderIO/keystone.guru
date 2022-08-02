@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('views/dungeonroute.discover.search.page_title')
])

@inject('seasonService', 'App\Service\Season\SeasonService')

@section('header-title')
    {{ __('views/dungeonroute.discover.search.header') }}
@endsection
<?php
/** @var $currentExpansion \App\Models\Expansion */
/** @var $activeExpansions \Illuminate\Support\Collection|\App\Models\Expansion[] */
/** @var $allAffixGroupsByActiveExpansion \Illuminate\Support\Collection|\App\Models\AffixGroup[] */
/** @var $featuredAffixesByActiveExpansion \Illuminate\Support\Collection|\App\Models\Affix[] */
/** @var $seasonService \App\Service\Season\SeasonService */
/** @var $currentSeason \App\Models\Season */
/** @var $nextSeason \App\Models\Season|null */
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/search', 'options' =>  [
        'levelMin' => config('keystoneguru.levels.min'),
        'levelMax' => config('keystoneguru.levels.max'),
        'limit' => config('keystoneguru.discover.limits.search'),
        'currentSeason' => $currentSeason->id,
        'nextSeason' => optional($nextSeason)->id,
        'currentExpansion' => $currentExpansion->shortname,
    ]
])

@section('scripts')
    @parent

    @foreach($allAffixGroupsByActiveExpansion as $expansion => $affixgroups)
        @include('common.handlebars.affixgroupsselect', [
            'id' => 'filter_affixes_' . $expansion,
            'affixgroups' => $affixgroups,
        ])
    @endforeach
@endsection

@section('content')
    <div id="search_dungeon">
        <ul id="search_dungeon_select_tabs" class="nav nav-tabs" role="tablist">
            @if($nextSeason !== null)
                <li class="nav-item">
                    <a id="season-{{ $nextSeason->id }}-search-tab"
                       class="nav-link active"
                       href="#season-{{ $nextSeason->id }}-search-content"
                       role="tab"
                       aria-controls="season-{{ $nextSeason->id }}-search-content"
                       aria-selected="true"
                       data-toggle="tab"
                       data-season="{{ $nextSeason->id }}"
                    >{{ $nextSeason->name }}</a>
                </li>
            @endif
            <li class="nav-item">
                <a id="season-{{ $currentSeason->id }}-search-tab"
                   class="nav-link {{ $nextSeason === null ? 'active' : '' }}"
                   href="#season-{{ $currentSeason->id }}-search-content"
                   role="tab"
                   aria-controls="season-{{ $currentSeason->id }}-search-content"
                   aria-selected="{{ $nextSeason === null ? 'true' : 'false' }}"
                   data-toggle="tab"
                   data-season="{{ $currentSeason->id }}"
                >{{ $currentSeason->name }}</a>
            </li>
            @foreach($activeExpansions as $expansion)
                <li class="nav-item">
                    <a id="{{ $expansion->shortname }}-search-tab"
                       class="nav-link"
                       href="#{{ $expansion->shortname }}-search-content"
                       role="tab"
                       aria-controls="{{ $expansion->shortname }}-search-content"
                       aria-selected="false"
                       data-toggle="tab"
                       data-expansion="{{ $expansion->shortname }}"
                    >{{ __($expansion->name) }}</a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @if($nextSeason !== null)
                <div id="season-{{ $nextSeason->id }}-search-content"
                     class="tab-pane fade show active"
                     role="tabpanel"
                     aria-labelledby="season-{{ $nextSeason->id }}-search-content">
                    @include('common.dungeon.grid', ['dungeons' => $nextSeason->dungeons, 'names' => true, 'selectable' => true])
                </div>
            @endif
            <div id="season-{{ $currentSeason->id }}-search-content"
                 class="tab-pane fade show {{ $nextSeason === null ? 'active' : '' }}"
                 role="tabpanel"
                 aria-labelledby="season-{{ $currentSeason->id }}-search-content">
                @include('common.dungeon.grid', ['dungeons' => $currentSeason->dungeons, 'names' => true, 'selectable' => true])
            </div>
            @foreach($activeExpansions as $expansion)
                <div id="{{ $expansion->shortname }}-search-content"
                     class="tab-pane fade show"
                     role="tabpanel"
                     aria-labelledby="{{ $expansion->shortname }}-search-content">
                    @include('common.dungeon.grid', ['expansion' => $expansion, 'names' => true, 'selectable' => true])
                </div>
            @endforeach
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-xl-3">

        </div>
        <div id="route_list_current_filters" class="col-xl-9">

        </div>
    </div>
    <div class="row">
        <div class="col-xl-3">
            @component('common.dungeonroute.search.filter', ['key' => 'title', 'text' => __('views/dungeonroute.discover.search.title')])
                {!! Form::text('title', request('title'), ['id' => 'title', 'class' => 'form-control', 'placeholder' => __('views/dungeonroute.discover.search.title_placeholder'), 'autocomplete' => 'off']) !!}
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'level', 'text' => __('views/dungeonroute.discover.search.key_level')])
                <input id="level" type="text" name="level" value="{{ old('level') }}" style="display: none;"/>
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'affixes', 'text' => __('views/dungeonroute.discover.search.affixes')])
                @foreach($allAffixGroupsByActiveExpansion as $expansion => $affixgroups)
                    <div class="filter_affix {{ $expansion }}" style="display: none;">
                        <div class="row">
                            <div class="col">
                                {!! Form::select(sprintf('filter_affixes_%s[]', $expansion), $affixgroups->pluck('text', 'id'), [],
                                    ['id' => 'filter_affixes_' . $expansion,
                                    'class' => 'form-control affixselect selectpicker',
                                    'multiple' => 'multiple',
                                    'title' => __('views/dungeonroute.discover.search.affixes_title'),
                                    'data-selected-text-format' => 'count > 1',
                                    'data-count-selected-text' => __('views/dungeonroute.discover.search.affixes_selected')]) !!}
                            </div>
                        </div>

                        <?php
                        /** @noinspection PhpUndefinedVariableInspection */
                        $featuredAffixes = $featuredAffixesByActiveExpansion->get($expansion);

                        $chunkedFeaturedAffixes = $featuredAffixes->chunk($featuredAffixes->count() < 9 ? 4 : 5);
                        ?>
                        @foreach($chunkedFeaturedAffixes as $affixRow)
                            <div class="row mt-2 pl-2 featured_affixes">
                                @foreach($affixRow as $affix)
                                    <?php /** @var $affix \App\Models\Affix */ ?>
                                    <div class="col px-xl-1">
                                        <div
                                            class="select_icon class_icon affix_icon_{{ strtolower($affix->key) }} selectable"
                                            data-toggle="tooltip" data-id="{{ $affix->id }}"
                                            title="{{ __($affix->description) }}"
                                            style="height: 24px;">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'enemy_forces', 'text' => __('views/dungeonroute.discover.search.enemy_forces')])
                <input id="enemy_forces" type="checkbox"
                       checked="checked"
                       data-toggle="toggle" data-width="100%" data-height="20px"
                       data-onstyle="success" data-offstyle="warning"
                       data-on="{{ __('views/dungeonroute.discover.search.enemy_forces_complete') }}"
                       data-off="{{ __('views/dungeonroute.discover.search.enemy_forces_incomplete') }}">
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'rating', 'text' => __('views/dungeonroute.discover.search.rating')])
                <input id="rating" type="text" name="level" value="{{ old('rating') }}" style="display: none;"/>
            @endcomponent
            @component('common.dungeonroute.search.filter', ['key' => 'user', 'text' => __('views/dungeonroute.discover.search.user'), 'expanded' => false])
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
