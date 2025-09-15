<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\Season\SeasonService;
use Illuminate\Support\Collection;

/**
 * @var GameVersion            $currentUserGameVersion
 * @var Expansion              $currentExpansion
 * @var Collection<AffixGroup> $allAffixGroupsByActiveExpansion
 * @var Collection<Affix>      $featuredAffixesByActiveExpansion
 * @var SeasonService          $seasonService
 * @var Season                 $currentSeason
 * @var Season|null            $nextSeason
 */

?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('view_dungeonroute.discover.search.page_title'),
])

@inject('seasonService', 'App\Service\Season\SeasonService')

@section('header-title')
    {{ __('view_dungeonroute.discover.search.header') }}
@endsection

@include('common.general.inline', ['path' => 'dungeonroute/discover/search', 'options' =>  [
        'targetContainerSelector' => '#route_list',
        'loadMoreSelector' => '#route_list_load_more',
        'currentFiltersSelector' => '#route_list_current_filters',
        'loaderSelector' => '#route_list_overlay',
        'gameVersion' => $currentUserGameVersion,
        'limit' => config('keystoneguru.discover.limits.search'),
        'defaultKeyLevelMin' => config('keystoneguru.keystone.levels.default_min'),
        'defaultKeyLevelMax' => config('keystoneguru.keystone.levels.default_max'),
        'currentSeason' => $currentSeason->id,
        'currentSeasonKeyLevelMin' => $currentSeason->key_level_min,
        'currentSeasonKeyLevelMax' => $currentSeason->key_level_max,
        'nextSeason' => $nextSeason?->id,
        'nextSeasonKeyLevelMin' => $nextSeason?->key_level_min,
        'nextSeasonKeyLevelMax' => $nextSeason?->key_level_max,
        'currentExpansion' => $currentExpansion->shortname,
    ],
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
    @include('common.dungeon.gridtabs', ['id' => 'search_dungeon', 'tabsId' => 'search_dungeon_select_tabs'])

    <!--suppress HtmlFormInputWithoutLabel -->
    <div class="row mb-2">
        <div class="col-xl-3">

        </div>
        <div id="route_list_current_filters" class="col-xl-9">

        </div>
    </div>
    <div class="row">
        <div class="col-xl-3">
            @component('common.search.filter', ['key' => 'title', 'text' => __('view_dungeonroute.discover.search.title')])
                {{ html()->text('title', request('title'))->id('title')->class('form-control')->placeholder(__('view_dungeonroute.discover.search.title_placeholder'))->attribute('autocomplete', 'off') }}
            @endcomponent
            @component('common.search.filter', ['key' => 'level', 'text' => __('view_dungeonroute.discover.search.key_level')])
                <input id="level" type="text" name="level" value="{{ old('level') }}" style="display: none;"/>
            @endcomponent
            @component('common.search.filter', ['key' => 'affixes', 'text' => __('view_dungeonroute.discover.search.affixes')])
                @foreach($allAffixGroupsByActiveExpansion as $expansion => $affixgroups)
                    <div class="filter_affix {{ $expansion }}" style="display: none;">
                        <div class="row">
                            <div class="col">
                                {{ html()->multiselect(sprintf('filter_affixes_%s[]', $expansion), $affixgroups->pluck('text', 'id'), [])->id('filter_affixes_' . $expansion)->class('form-control affixselect selectpicker')->attribute('title', __('view_dungeonroute.discover.search.affixes_title'))->data('selected-text-format', 'count > 1')->data('none-selected-text', __('view_dungeonroute.discover.search.select_affixes'))->data('count-selected-text', __('view_dungeonroute.discover.search.affixes_selected')) }}
                            </div>
                        </div>

                            <?php
                            /** @var Collection<Affix> $featuredAffixes */
                            $featuredAffixes = $featuredAffixesByActiveExpansion->get($expansion);

                            $chunkedFeaturedAffixes = $featuredAffixes->chunk($featuredAffixes->count() < 9 ? 4 : (int)($featuredAffixes->count() / 2));
                            ?>
                        @foreach($chunkedFeaturedAffixes as $affixRow)
                            <div class="row mt-2 pl-2 featured_affixes">
                                @foreach($affixRow as $affix)
                                        <?php /** @var Affix $affix */ ?>
                                    <div class="col px-xl-1">
                                        <div
                                            class="select_icon class_icon affix_icon_{{ $affix->image_name }} selectable"
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
            @component('common.search.filter', ['key' => 'enemy_forces', 'text' => __('view_dungeonroute.discover.search.enemy_forces')])
                <input id="enemy_forces" type="checkbox"
                       checked="checked"
                       data-toggle="toggle" data-width="100%" data-height="20px"
                       data-onstyle="success" data-offstyle="warning"
                       data-on="{{ __('view_dungeonroute.discover.search.enemy_forces_complete') }}"
                       data-off="{{ __('view_dungeonroute.discover.search.enemy_forces_incomplete') }}">
            @endcomponent
            @component('common.search.filter', ['key' => 'rating', 'text' => __('view_dungeonroute.discover.search.rating')])
                <input id="rating" type="text" name="level" value="{{ old('rating') }}" style="display: none;"/>
            @endcomponent
            @component('common.search.filter', ['key' => 'user', 'text' => __('view_dungeonroute.discover.search.user'), 'expanded' => false])
                {{ html()->text('user', request('user'))->id('user')->class('form-control')->placeholder(__('view_dungeonroute.discover.search.user_placeholder'))->attribute('autocomplete', 'off') }}
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
