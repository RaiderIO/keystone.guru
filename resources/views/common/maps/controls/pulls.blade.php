<?php
/** @var bool $edit */
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */
?>
@include('common.general.inline', ['path' => 'common/maps/killzonessidebar', 'options' => [
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
    'edit' => $edit
]])

<nav id="pulls_sidebar"
     class="route_manipulation_tools top right row no-gutters map_fade_out {{ $isMobile ? 'mobile' : 'active' }}">
    <div class="{{ $edit ? 'edit' : '' }} bg-header">
        <div id="pulls_sidebar_trigger" class="handle">
            <i class="fas {{ $isMobile ? 'fa-arrow-left' : 'fa-arrow-right' }}"></i>
        </div>

        @if($edit)
            <div class="p-1">
                <div class="row pr-2 mb-2 no-gutters">
                    <div class="col-auto" data-toggle="tooltip" title="{{ __('Settings') }}">
                        <button class="btn btn-info w-100" data-toggle="modal"
                                data-target="#route_settings_modal">
                            <i class='fas fa-cog'></i>
                        </button>
                    </div>
                    <div class="col pl-2 pr-2">
                        <div id="killzones_new_pull" class="btn btn-success w-100">
                            <i class="fas fa-plus"></i> {{__('New pull')}}
                        </div>
                    </div>
                    <div class="col-auto" data-toggle="tooltip" title="{{ __('Delete all pulls') }}">
                        <button id="killzones_pulls_settings_delete_all" class="btn btn-danger w-100">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if($edit)
        <div class="">
            <div id="edit_route_enemy_forces_container"></div>
        </div>
        @else
        <div class="row pr-2 mb-2 no-gutters">
            <div class="col-auto" data-toggle="tooltip" title="{{ __('Settings') }}">
                <button class="btn btn-info w-100" data-toggle="modal" data-target="#route_settings_modal">
                    <i class='fas fa-cog'></i>
                </button>
            </div>
            <div class="col">
                <div id="edit_route_enemy_forces_container" class="pt-1">

                </div>
            </div>
        </div>
        @endif

        <div class="pulls_container" data-simplebar>

            <div id="killzones_loading" class="row no-gutters">
                <div class="col text-center">
                    <h5>{{ __('Loading...') }}</h5>
                </div>
            </div>
            <div id="killzones_no_pulls" class="row no-gutters" style="display: none;">
                <div class="col text-center">
                    <h5>
                        @if($edit)
                            {{ __('No pulls created. Click on the button above or on an enemy to add them to your first pull.') }}
                        @else
                            {{ __('No pulls created.') }}
                        @endif
                    </h5>
                </div>
            </div>
            <div id="killzones_container" class="mr-3">
            </div>
        </div>

    </div>
</nav>