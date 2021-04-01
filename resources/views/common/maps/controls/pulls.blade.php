<?php
/** @var bool $edit */
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */
?>
@include('common.general.inline', ['path' => 'common/maps/killzonessidebar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'sidebarScrollSelector' => '#pulls_sidebar .pulls_container',
    'sidebarToggleSelector' => '#killzonesidebarToggle',
    'anchor' => 'right',
    'newKillZoneSelector' => '#new_pull_btn',
    'killZonesContainerSelector' => '#killzones_container',
    'killZonesPullsSettingsMapNumberStyleSelector' => '#killzones_pulls_settings_map_number_style',
    'killZonesPullsSettingsNumberStyleSelector' => '#killzones_pulls_settings_number_style',
    'killZonesPullsSettingsDeleteAllSelector' => '#killzones_pulls_settings_delete_all',
    'edit' => $edit
]])

<nav id="pulls_sidebar" class="route_manipulation_tools top right row no-gutters map_fade_out">
    <div class="{{ $edit ? 'edit' : '' }} bg-header">
        @if($edit)
            <div class="">
                <div class="container">
                    <div class="row mb-2 mt-2 no-gutters">
                        <div class="col-2">
                            <button class="btn btn-info w-100" data-toggle="modal"
                                    data-target="#route_settings_modal">
                                <i class='fas fa-cog'></i>
                            </button>
                        </div>
                        <div class="col-8 pl-2 pr-2">
                            <div id="killzones_new_pull" class="btn btn-success w-100">
                                <i class="fas fa-plus"></i> {{__('New pull')}}
                            </div>
                        </div>
                        <div class="col-2">
                            <button id="killzones_pulls_settings_delete_all" class="btn btn-danger w-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="">
            <div id="edit_route_enemy_forces_container"></div>
        </div>

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