<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Illuminate\Support\Collection;

/**
 * @var bool                                     $showAds
 * @var Dungeon                                  $dungeon
 * @var Season                                   $season
 * @var bool                                     $embed
 * @var string                                   $embedStyle
 * @var bool                                     $isMobile
 * @var integer                                  $defaultState
 * @var bool                                     $hideOnMove
 * @var bool                                     $showAllEnabled
 * @var Collection<AffixGroup>                   $allAffixGroupsByActiveExpansion
 * @var Collection<Affix>                        $featuredAffixesByActiveExpansion
 * @var int                                      $keyLevelMin
 * @var int                                      $keyLevelMax
 * @var int                                      $itemLevelMin
 * @var int                                      $itemLevelMax
 * @var int                                      $playerDeathsMin
 * @var int                                      $playerDeathsMax
 * @var Collection<WeeklyAffixGroup>             $seasonWeeklyAffixGroups
 * @var Collection<CharacterClassSpecialization> $characterClassSpecializations
 * @var Collection<GameServerRegion>             $allRegions
 */

// By default, show it if we're not mobile, but allow overrides
$heatmapSearchSidebarState  = (int)($_COOKIE['heatmap_search_sidebar_state'] ?? 1);
$defaultState               ??= $isMobile ? 0 : $heatmapSearchSidebarState;
$heatmapSearchEnabled       = (bool)($_COOKIE['heatmap_search_enabled'] ?? 1);
$filterExpandedCookiePrefix = 'heatmap_search_expanded';

$shouldShowHeatmapSearchSidebar = $defaultState === 1;
$hideOnMove                     ??= $isMobile;
$showAds                        ??= true;
/** @var Collection<AffixGroup> $affixGroups */
$affixGroups = $allAffixGroupsByActiveExpansion->get($season->expansion->shortname);
/** @var Collection<Affix> $featuredAffixes */
$featuredAffixes = $featuredAffixesByActiveExpansion->get($season->expansion->shortname);

$allRegions = $allRegions->sort(function (GameServerRegion $a, GameServerRegion $b) {
    // If one of them is "World", it comes first
    if ($a->short === GameServerRegion::WORLD) return -1;
    if ($b->short === GameServerRegion::WORLD) return 1;

    // Otherwise, sort by ID ascending
    return $a->id <=> $b->id;
});

?>
@include('common.general.inline', ['path' => 'common/maps/heatmapsearchsidebar', 'options' => [
    'stateCookie' => 'heatmap_search_sidebar_state',
    'defaultState' => $defaultState,
    'hideOnMove' => $hideOnMove,
    'currentFiltersSelector' => '#heatmap_search_options_current_filters',
    'loaderSelector' => '#heatmap_search_loader',

    'keyLevelMin' => $keyLevelMin,
    'keyLevelMax' => $keyLevelMax,
    'itemLevelMin' => $itemLevelMin,
    'itemLevelMax' => $itemLevelMax,
    'playerDeathsMin' => $playerDeathsMin,
    'playerDeathsMax' => $playerDeathsMax,
    'durationMin' => 5,
    'durationMax' => 60,

    'enabledStateCookie' => 'heatmap_search_enabled',
    'enabledStateSelector' => '#heatmap_search_toggle',
    'filterEventTypeContainerSelector' => '#filter_event_type_container',
    'filterEventTypeSelector' => 'input[name="event_type"]',
    'filterDataTypeContainerSelector' => '#filter_data_type_container',
    'filterDataTypeSelector' => 'input[name="data_type"]',
    'filterRegionContainerSelector' => '#filter_region_container',
    'filterRegionSelector' => 'input[name="region"]',
    'filterKeyLevelSelector' => '#filter_key_level',
    'filterItemLevelSelector' => '#filter_item_level',
    'filterPlayerDeathsSelector' => '#filter_player_deaths',
    'filterAffixesSelector' => '.select_icon.class_icon.selectable',
    'filterWeeklyAffixGroupsSelector' => '#filter_weekly_affix_groups',
    'filterSpecializationsSelector' => '#filter_specializations',
    'filterDurationSelector' => '#filter_duration',

    'filterCollapseNames' => ['keyLevel', 'includeAffixIds', 'duration'],
    'filterCookiePrefix' => $filterExpandedCookiePrefix,

    'leafletHeatOptionsMinOpacitySelector' => '#heatmap_heat_option_min_opacity',
    'leafletHeatOptionsMaxZoomSelector' => '#heatmap_heat_option_max_zoom',
    'leafletHeatOptionsMaxSelector' => '#heatmap_heat_option_max',
    'leafletHeatOptionsRadiusSelector' => '#heatmap_heat_option_radius',
    'leafletHeatOptionsBlurSelector' => '#heatmap_heat_option_blur',
    'leafletHeatOptionsGradientSelector' => '#heatmap_heat_option_gradient',
    'leafletHeatOptionsPaneSelector' => '#heatmap_heat_option_pane',

    'dependencies' => ['common/maps/map'],
    // Mobile sidebar options
    'sidebarSelector' => '#heatmap_search_sidebar',
    'sidebarToggleSelector' => '#heatmap_search_sidebar_trigger',
    'sidebarScrollSelector' => '#heatmap_search_sidebar .data_container',
    'anchor' => 'right',
    'edit' => $edit,
]])

@section('scripts')
    @parent

    @include('common.handlebars.affixweekselect', [
        'id' => 'filter_weekly_affix_groups',
        'seasonWeeklyAffixGroups' => $seasonWeeklyAffixGroups,
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
                <div class="col">
                </div>
                <div class="col-auto">
                    <input id="heatmap_search_toggle" type="checkbox"
                           {{ $heatmapSearchEnabled ? 'checked' : '' }}
                           data-toggle="toggle" data-width="100px" data-height="20px"
                           data-onstyle="primary" data-offstyle="primary"
                           data-on="{{ __('view_common.maps.controls.heatmapsearch.enabled') }}"
                           data-off="{{ __('view_common.maps.controls.heatmapsearch.disabled') }}">
                </div>
            </div>
        </div>

        <div class="data_container p-2" data-simplebar>
            <div id="heatmap_search_options_container" class="px-1">
                <div class="row">
                    <div class="col">
                        <div id="heatmap_search_options_current_filters">

                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div id="filter_event_type_container" class="btn-group btn-group-toggle w-100"
                         data-toggle="buttons">
                        <label class="btn btn-secondary active">
                            <input type="radio" name="event_type"
                                   class="{{ CombatLogEventEventType::NpcDeath->value }}"
                                   value="{{ CombatLogEventEventType::NpcDeath->value }}"
                                   checked>
                            <img src="{{ url('images/spells/achievement_bg_killxenemies_generalsroom.jpg') }}"
                                 alt="{{ __('view_common.maps.controls.heatmapsearch.npc_death_alt') }}"
                                 class="filter_event_type_icon">
                            {{ __('combatlogeventtypes.npc_death') }}
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="event_type"
                                   class="{{ CombatLogEventEventType::PlayerDeath->value }}"
                                   value="{{ CombatLogEventEventType::PlayerDeath->value }}">
                            <img src="{{ url('images/spells/ability_rogue_feigndeath.jpg') }}"
                                 alt="{{ __('view_common.maps.controls.heatmapsearch.player_death_alt') }}"
                                 class="filter_event_type_icon">
                            {{ __('combatlogeventtypes.player_death') }}
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="event_type"
                                   class="{{ CombatLogEventEventType::PlayerSpell->value }}"
                                   value="{{ CombatLogEventEventType::PlayerSpell->value }}">
                            <img src="{{ url('images/spells/spell_nature_bloodlust.jpg') }}"
                                 alt="{{ __('view_common.maps.controls.heatmapsearch.bloodlust_alt') }}"
                                 class="filter_event_type_icon">
                            {{ __('combatlogeventtypes.player_spell') }}
                        </label>
                    </div>
                </div>

                @component('common.forms.labelinput', [
                    'id' => 'filter_data_type_container',
                    'name' => 'filter_data_type',
                    'label' => __('view_common.maps.controls.heatmapsearch.data_type'),
                    'title' => __('view_common.maps.controls.heatmapsearch.data_type_title'),
                ])
                    <div class="btn-group btn-group-toggle w-100 mb-1"
                         data-toggle="buttons">
                        <label class="btn btn-secondary active">
                            <input type="radio" name="data_type"
                                   class="{{ CombatLogEventDataType::PlayerPosition->value }}"
                                   value="{{ CombatLogEventDataType::PlayerPosition->value }}"
                                   checked>
                            <i class="fas fa-map"></i> {{ __('combatlogdatatypes.player_position') }}
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="data_type"
                                   class="{{ CombatLogEventDataType::EnemyPosition->value }}"
                                   value="{{ CombatLogEventDataType::EnemyPosition->value }}">
                            <i class="fas fa-map-marked-alt"></i> {{ __('combatlogdatatypes.enemy_position') }}
                        </label>
                    </div>
                @endcomponent


                <div class="form-group">
                    <div id="filter_region_container" class="btn-group btn-group-toggle w-100"
                         data-toggle="buttons">
                        @foreach($allRegions as $region)
                            <label class="btn btn-secondary {{ $region->short === 'world' ? 'active' : '' }}">
                                <input type="radio" name="region"
                                       class="{{ $region->short }}"
                                       value="{{ $region->short }}"
                                        {{ $region->short === 'world' ? 'checked' : '' }}
                                >
                                <img src="{{ url(sprintf('images/flags/%s.png', $region->short)) }}"
                                     alt="{{ __($region->name) }}"
                                     class="filter_region_icon">
                                {{ __($region->name) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                @component('common.forms.labelinput', [
                    'name' => 'key_level',
                    'label' => __('view_common.maps.controls.heatmapsearch.key_level')
                ])
                    <input id="filter_key_level" type="text" name="key_level" value="{{ old('key_level') }}"/>
                @endcomponent

                @component('common.forms.labelinput', [
                    'name' => 'item_level',
                    'label' => __('view_common.maps.controls.heatmapsearch.item_level'),
                ])
                    <input id="filter_item_level" type="text" name="item_level" value="{{ old('item_level') }}"/>
                @endcomponent

                @component('common.forms.labelinput', [
                    'name' => 'player_deaths',
                    'label' => __('view_common.maps.controls.heatmapsearch.player_deaths')
                ])
                    <input id="filter_player_deaths" type="text" name="player_deaths"
                           value="{{ old('player_deaths') }}"/>
                @endcomponent

                @component('common.forms.labelinput', [
                    'name' => 'duration',
                    'label' => __('view_common.maps.controls.heatmapsearch.duration'),
                ])
                    <input id="filter_duration" type="text" name="duration" value="{{ old('duration') }}"/>
                @endcomponent

                @component('common.forms.labelinput', [
                    'name' => 'weekly_affix_groups',
                    'label' => __('view_common.maps.controls.heatmapsearch.weekly_affix_groups'),
                ])
                    <div class="filter_affix">
                        <div class="row">
                            <div class="col">
                                {!!
                                    Form::select(
                                        'filter_weekly_affix_groups[]',
                                        $seasonWeeklyAffixGroups->mapWithKeys(function(WeeklyAffixGroup $seasonWeeklyAffixGroup){
                                            return [$seasonWeeklyAffixGroup->week => $seasonWeeklyAffixGroup->affixGroup->text];
                                        }), [],
                                        [
                                            'id' => 'filter_weekly_affix_groups',
                                            'name' => 'weekly_affix_groups',
                                            'class' => 'form-control affixselect selectpicker',
                                            'multiple' => 'multiple'
                                        ]
                                    )
                                 !!}
                            </div>
                        </div>
                    </div>
                @endcomponent

                @component('common.forms.labelinput', [
                    'name' => 'filter_specializations',
                    'label' => __('view_common.maps.controls.heatmapsearch.specializations'),
                ])
                    {!!
                        Form::select(
                            'filter_specializations[]',
                            $characterClassSpecializations->groupBy(function(CharacterClassSpecialization $characterClassSpecialization) {
                                return __($characterClassSpecialization->class->name);
                            })->mapWithKeys(function (Collection $specializations, string $className) {
                                return [
                                    $className => $specializations->mapWithKeys(function(CharacterClassSpecialization $characterClassSpecialization){
                                        return [
                                            // No translation is intended!
                                            $characterClassSpecialization->specialization_id => __($characterClassSpecialization->name)
                                        ];
                                    })
                                ];
                            })->toArray(),
                            [],
                            [
                                'id' => 'filter_specializations',
                                'name' => 'specializations',
                                'class' => 'form-control selectpicker',
                                'multiple' => 'multiple'
                            ]
                        )
                     !!}
                @endcomponent

                @if($dungeon->gameVersion->has_seasons)
                    {{--                    @component('common.forms.labelinput', ['key' => 'season', 'text' => __('view_common.maps.controls.heatmapsearch.season'), 'expanded' => $expandedAffixWeek])--}}
                    {{--                        <div class="filter_affix">--}}
                    {{--                            <div class="row">--}}
                    {{--                                <div class="col">--}}
                    {{--                                    {!! Form::select('filter_season[]',--}}
                    {{--                                        $seasonWeeklyAffixGroups->mapWithKeys(function(WeeklyAffixGroup $seasonWeeklyAffixGroup){--}}
                    {{--                                            return [$seasonWeeklyAffixGroup->week => $seasonWeeklyAffixGroup->affixGroup->text];--}}
                    {{--                                        }), [],--}}
                    {{--                                        ['id' => 'filter_season',--}}
                    {{--                                        'class' => 'form-control affixselect selectpicker',--}}
                    {{--                                        'title' => __('view_common.maps.controls.heatmapsearch.season_title')]) !!}--}}
                    {{--                                </div>--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}
                    {{--                    @endcomponent--}}

                    @component('common.forms.labelinput', [
                        'name' => 'affixes',
                        'label' => __('view_common.maps.controls.heatmapsearch.affixes'),
                    ])
                        <div class="filter_affix">
                                <?php
                                $chunkedFeaturedAffixes = $featuredAffixes->chunk($featuredAffixes->count() < 9 ? 4 : (int)($featuredAffixes->count() / 2));
                                ?>
                            @foreach($chunkedFeaturedAffixes as $affixRow)
                                <div class="row mt-2 pl-2 featured_affixes">
                                    @foreach($affixRow as $affix)
                                            <?php /** @var Affix $affix */ ?>
                                        <div class="col">
                                            <div
                                                class="select_icon class_icon affix_icon_{{ $affix->image_name }} selectable m-auto"
                                                data-toggle="tooltip" data-id="{{ $affix->affix_id }}"
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

                @if(Auth::check() && Auth::user()->hasRole('admin'))
                    @component('common.search.filter', ['key' => 'heatoptions', 'text' => __('view_common.maps.controls.heatmapsearch.heat_options'), 'expanded' => true])
                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_min_opacity">
                                    {{ __('view_common.maps.controls.heatmapsearch.heat_option.min_opacity') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_min_opacity" type="text" name="min_opacity" value="0.1"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_max_zoom">
                                    {{ __('view_common.maps.controls.heatmapsearch.heat_option.max_zoom') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_max_zoom" type="text" name="max_zoom" value="5"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_max">
                                    {{ __('view_common.maps.controls.heatmapsearch.heat_option.max') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_max" type="text" name="max" value="1.0"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_radius">
                                    {{ __('view_common.maps.controls.heatmapsearch.heat_option.radius') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_radius" type="text" name="radius" value="35"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_blur">
                                    {{ __('view_common.maps.controls.heatmapsearch.heat_option.blur') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_blur" type="text" name="blur" value="15"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_gradient">
                                    {{ __('view_common.maps.controls.heatmapsearch.heat_option.gradient') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_gradient" type="text" name="gradient"
                                       value='{".4":"blue",".6":"cyan",".7":"lime",".8":"yellow","1":"red"}'/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_pane">
                                    {{ __('view_common.maps.controls.heatmapsearch.heat_option.pane') }}
                                </label>
                            </div>
                            <div class="col">
                                {{ Form::select('pane',
                                    ['overlayPane' => 'Overlay', 'markerPane' => 'Marker', 'tooltipPane' => 'Tooltip'],
                                    'overlayPane',
                                    ['id' => 'heatmap_heat_option_pane', 'class' => 'selectpicker']
                                ) }}
                            </div>
                        </div>
                    @endcomponent
                @endif
            </div>
        </div>

    </div>
</nav>
