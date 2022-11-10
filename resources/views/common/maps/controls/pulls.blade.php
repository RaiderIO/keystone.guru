<?php
/** @var bool $edit */
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */
/** @var bool $embed */
/** @var bool $isMobile */
/** @var bool $defaultState */
/** @var bool $hideOnMove */
/** @var bool $showAllEnabled */

// By default, show it if we're not mobile, but allow overrides
$defaultState = $defaultState ?? !$isMobile;
$hideOnMove   = $hideOnMove ?? $isMobile;
?>
@include('common.general.inline', ['path' => 'common/maps/killzonessidebar', 'options' => [
    'defaultState' => $defaultState,
    'hideOnMove' => $hideOnMove,
    'dependencies' => ['common/maps/map'],
    // Mobile sidebar options
    'sidebarSelector' => '#pulls_sidebar',
    'sidebarToggleSelector' => '#pulls_sidebar_trigger',
    'sidebarScrollSelector' => '#pulls_sidebar .pulls_container',
    'anchor' => 'right',
    'newKillZoneSelector' => '#new_pull_btn',
    'killZonesContainerSelector' => '#killzones_container',
    'killZonesPullsSettingsMapNumberStyleSelector' => '#killzones_pulls_settings_map_number_style',
    'killZonesPullsSettingsNumberStyleSelector' => '#killzones_pulls_settings_number_style',
    'killZonesPullsSettingsDeleteAllSelector' => '#killzones_pulls_settings_delete_all',
    'killZonesPullsSettingsPullsSidebarFloorSwitchVisibilitySelector' => '#pulls_sidebar_floor_switch_visibility',
    'edit' => $edit
]])

<nav id="pulls_sidebar"
     class="route_manipulation_tools top right row no-gutters map_fade_out
             {{ $embed ? 'embed' : '' }}
     {{ $isMobile ? 'mobile' : '' }}
     {{ $defaultState ? 'active' : '' }}
         ">
    <div class="{{ $edit ? 'edit' : '' }} bg-header">
        <div id="pulls_sidebar_trigger" class="handle">
            <i class="fas {{ $defaultState ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i>
        </div>

        <?php
        // We don't want the enemy forces code for $embedding - that's true. BUT if we have a speedrun enabled we do not show
        // the enemy forces, so then we DO want to have this executed to add the speedrun npcs in that scenario.
        ?>
        @if(!$embed || $dungeon->speedrun_enabled)
            @if($edit)
                <div class="p-1">
                    <div class="row pr-2 mb-2 no-gutters">
                        <div class="col-auto" data-toggle="tooltip"
                             title="{{ __('views/common.maps.controls.pulls.settings_title') }}">
                            <button class="btn btn-info w-100" data-toggle="modal" data-target="#map_settings_modal">
                                <i class='fas fa-cog'></i>
                            </button>
                        </div>
                        <div class="col pl-2 pr-2">
                            <div id="killzones_new_pull" class="btn btn-success w-100">
                                <i class="fas fa-plus"></i> {{__('views/common.maps.controls.pulls.new_pull')}}
                            </div>
                        </div>
                        <div class="col-auto" data-toggle="tooltip"
                             title="{{ __('views/common.maps.controls.pulls.delete_all_pulls_title') }}">
                            <button id="killzones_pulls_settings_delete_all" class="btn btn-danger w-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @if( $dungeon->speedrun_enabled )
                    @include('common.maps.controls.dungeonspeedrunrequirednpcs', ['edit' => true, 'showAllEnabled' => $showAllEnabled])
                    <hr class="my-2">
                @else
                    <div id="edit_route_enemy_forces_container"></div>
                @endif
            @else
                <div class="row p-1 pr-2 mb-2 no-gutters">
                    <div class="col-auto" data-toggle="tooltip"
                         title="{{ __('views/common.maps.controls.pulls.settings_title') }}">
                        <button class="btn btn-info w-100" data-toggle="modal" data-target="#map_settings_modal">
                            <i class='fas fa-cog'></i>
                        </button>
                    </div>
                    <div class="col">
                        @if( $dungeon->speedrun_enabled )
                            @include('common.maps.controls.dungeonspeedrunrequirednpcs', ['edit' => false, 'showAllEnabled' => $showAllEnabled])
                        @else
                            <div id="edit_route_enemy_forces_container" class="pt-1"></div>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        <div class="pulls_container {{ $dungeon->speedrun_enabled ? 'has_speedrun' : '' }}" data-simplebar>

            <div id="killzones_loading" class="row no-gutters">
                <div class="col text-center">
                    <h5>{{ __('views/common.maps.controls.pulls.loading') }}</h5>
                </div>
            </div>
            <div id="killzones_no_pulls" class="row no-gutters" style="display: none;">
                <div class="col text-center">
                    <h5>
                        @if($edit)
                            {{ __('views/common.maps.controls.pulls.no_pulls_created_edit') }}
                        @else
                            {{ __('views/common.maps.controls.pulls.no_pulls_created_view') }}
                        @endif
                    </h5>
                </div>
            </div>
            <div id="killzones_container">
            </div>
        </div>

    </div>
</nav>
