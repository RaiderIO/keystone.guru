<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

/**
 * @var bool                   $showAds
 * @var Dungeon                $dungeon
 * @var bool                   $embed
 * @var string                 $embedStyle
 * @var bool                   $isMobile
 * @var integer                $defaultState
 * @var bool                   $hideOnMove
 * @var bool                   $showAllEnabled
 * @var Collection<AffixGroup> $allAffixGroupsByActiveExpansion
 * @var Collection<Affix>      $featuredAffixesByActiveExpansion
 */

// By default, show it if we're not mobile, but allow overrides
$pullsSidebarState              = (int)($_COOKIE['pulls_sidebar_state'] ?? 1);
$defaultState                   ??= $isMobile ? 0 : $pullsSidebarState;
$shouldShowHeatmapSearchSidebar = $defaultState === 1;
$hideOnMove                     ??= $isMobile;
$showAds                        ??= true;
/** @var $affixGroups Collection<AffixGroup> */
$affixGroups = $allAffixGroupsByActiveExpansion->get($dungeon->expansion->shortname);
/** @var $featuredAffixes Collection<Affix> */
$featuredAffixes = $featuredAffixesByActiveExpansion->get($dungeon->expansion->shortname);
?>
@include('common.general.inline', ['path' => 'common/maps/heatmapsearchsidebar', 'options' => [
    'stateCookie' => 'heatmap_search_sidebar_state',
    'defaultState' => $defaultState,
    'hideOnMove' => $hideOnMove,
    'dependencies' => ['common/maps/map'],
    // Mobile sidebar options
    'sidebarSelector' => '#heatmap_search_sidebar',
    'sidebarToggleSelector' => '#heatmap_search_sidebar_trigger',
    'sidebarScrollSelector' => '#heatmap_search_sidebar .heatmap_search_container',
    'anchor' => 'right',
    'edit' => $edit,
]])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', [
        'id' => 'filter_affixes',
        'affixgroups' => $affixGroups,
    ])
@endsection

<!--suppress HtmlFormInputWithoutLabel -->
<nav id="heatmap_search_sidebar"
     class="route_sidebar top right row no-gutters map_fade_out
     {{ $isMobile ? 'mobile' : '' }}
     {{ $shouldShowHeatmapSearchSidebar ? 'active' : '' }}
     {{ $showAds ? 'ad_loaded' : '' }}
         ">
    <div class="bg-header">
        <div id="heatmap_search_sidebar_trigger" class="handle">
            <i class="fas {{ $shouldShowHeatmapSearchSidebar ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i>
        </div>

        <div class="p-1">
            <div class="row pr-2 mb-2 no-gutters">
                <div class="col-auto" data-toggle="tooltip"
                     title="{{ __('view_common.maps.controls.heatmapsearch.settings_title') }}">
                    <button class="btn btn-info w-100" data-toggle="modal" data-target="#map_settings_modal">
                        <i class='fas fa-cog'></i>
                    </button>
                </div>
                <div class="col pl-2 pr-2">
                    <div id="heatmap_search_filter_btn" class="btn btn-success w-100">
                        <i class="fas fa-filter"></i> {{__('view_common.maps.controls.heatmapsearch.filter')}}
                    </div>
                </div>
            </div>
        </div>

        <div class="heatmap_search_container p-2" data-simplebar>
            <div id="heatmap_search_options_container">
                @component('common.search.filter', ['key' => 'level', 'text' => __('view_common.maps.controls.heatmapsearch.key_level')])
                    <input id="level" type="text" name="level" value="{{ old('level') }}"/>
                @endcomponent
                @if($dungeon->gameVersion->has_seasons)
                    @component('common.search.filter', ['key' => 'affixes', 'text' => __('view_common.maps.controls.heatmapsearch.affixes')])
                        <div class="filter_affix">
                            <div class="row">
                                <div class="col">
                                    {!! Form::select('filter_affixes[]', $affixGroups->pluck('text', 'id'), [],
                                        ['id' => 'filter_affixes',
                                        'class' => 'form-control affixselect selectpicker',
                                        'multiple' => 'multiple',
                                        'title' => __('view_dungeonroute.discover.search.affixes_title'),
                                        'data-selected-text-format' => 'count > 1',
                                        'data-count-selected-text' => __('view_dungeonroute.discover.search.affixes_selected')]) !!}
                                </div>
                            </div>
                                <?php
                                $chunkedFeaturedAffixes = $featuredAffixes->chunk($featuredAffixes->count() < 9 ? 4 : 5);
                                ?>
                            @foreach($chunkedFeaturedAffixes as $affixRow)
                                <div class="row mt-2 pl-2 featured_affixes">
                                    @foreach($affixRow as $affix)
                                            <?php /** @var $affix Affix */ ?>
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
                    @endcomponent
                @endif
            </div>
        </div>

    </div>
</nav>
