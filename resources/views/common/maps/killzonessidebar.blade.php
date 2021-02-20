<?php
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */

$showRouteSettings = $show['route-settings'] ?? false;
?>
@include('common.general.inline', ['path' => 'common/maps/killzonessidebar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'sidebarSelector' => '#killzonesidebar',
    'sidebarScrollSelector' => '#killzonesidebar .sidebar-content',
    'sidebarToggleSelector' => '#killzonesidebarToggle',
    'anchor' => 'right',
    'newKillZoneSelector' => '#new_pull_btn',
    'killZonesContainerSelector' => '#killzones_container',
    'killZonesPullsSettingsSelector' => '#killzones_pulls_settings_container',
    'killZonesPullsSettingsMapNumberStyleSelector' => '#killzones_pulls_settings_map_number_style',
    'killZonesPullsSettingsNumberStyleSelector' => '#killzones_pulls_settings_number_style',
    'killZonesPullsSettingsDeleteAllSelector' => '#killzones_pulls_settings_delete_all',
    'edit' => $edit
]])

@section('sidebar-sticky')
    @if($edit)
        <div class="container">
            <div class="form-group">
                <div class="row mb-2 mt-2 no-gutters">
                    <div class="col-10 pr-2">
                        <div id="killzones_new_pull" class="btn btn-success w-100">
                            <i class="fas fa-plus"></i> {{__('New pull')}}
                        </div>
                    </div>
                    <div class="col-2">
                        <button class="btn btn-info w-100" data-toggle="modal"
                                data-target="#route_settings_modal">
                            <i class='fas fa-cog'></i>
                        </button>
                        {{--                        <button class="btn btn-info w-100"--}}
                        {{--                                data-toggle="collapse" data-target="#killzones_pulls_settings_container"--}}
                        {{--                                aria-expanded="false" aria-controls="killzones_pulls_settings_container">--}}
                        {{--                            <i class="fas fa-cog"></i>--}}
                        {{--                        </button>--}}
                    </div>
                </div>
            </div>

            <div id="killzones_pulls_settings_container" class="collapse">
                <div class="form-group">
                    <div class="row">
                        <div class="col">
                            <button id="killzones_pulls_settings_delete_all" class="btn btn-danger w-100">
                                <i class="fas fa-trash"></i> {{ __('Delete all pulls') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@component('common.maps.sidebar', [
    'dungeon' => $dungeon,
    'header' => __('Pulls'),
    'anchor' => 'right',
    'id' => 'killzonesidebar',
    'edit' => $edit,
    /* Draw controls are injected here through drawcontrols.js */
    'customSubHeader' => '<div class="mt-4"><div id="edit_route_enemy_forces_container"></div></div>'
])
    <div id="killzones_loading" class="row">
        <div class="col text-center">
            <h5>{{ __('Loading...') }}</h5>
        </div>
    </div>
    <div id="killzones_no_pulls" class="row" style="display: none;">
        <div class="col text-center">
            @if($edit)
                <h5>{{ __('No pulls created. Click on the button above or on an enemy to add them to your first pull.') }}</h5>
            @else
                <h5>{{ __('No pulls created.') }}</h5>
            @endif
        </div>
    </div>
    <div id="killzones_container">
    </div>
@endcomponent



@component('common.general.modal', ['id' => 'route_settings_modal', 'size' => 'xl'])
    <ul class="nav nav-tabs" role="tablist">
        @if($showRouteSettings)
            <li class="nav-item">
                <a class="nav-link active" id="edit_route_tab" data-toggle="tab" href="#edit" role="tab"
                   aria-controls="edit_route" aria-selected="true">
                    {{ __('Route') }}
                </a>
            </li>
        @endif
        <li class="nav-item">
            <a class="nav-link {{ $showRouteSettings ? '' : 'active' }}"
               id="edit_route_map_settings_tab" data-toggle="tab" href="#map-settings" role="tab"
               aria-controls="edit_route_map_settings" aria-selected="false">
                {{ __('Map settings') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="edit_route_pull_settings_tab" data-toggle="tab" href="#pull-settings" role="tab"
               aria-controls="edit_route_pull_settings" aria-selected="false">
                {{ __('Pull settings') }}
            </a>
        </li>
    </ul>

    <div class="tab-content">
        @if($showRouteSettings)
            <div id="edit" class="tab-pane fade show active mt-3" role="tabpanel" aria-labelledby="edit_route_tab">
                @include('common.forms.createroute', ['model' => $model])
            </div>
        @endif
        <div id="map-settings" class="tab-pane fade {{ $showRouteSettings ? '' : 'show active' }} mt-3" role="tabpanel" aria-labelledby="edit_route_map_settings_tab">
            @include('common.forms.mapsettings', ['model' => $model])
        </div>
        <div id="pull-settings" class="tab-pane fade mt-3" role="tabpanel"
             aria-labelledby="edit_route_pull_settings_tab">
            @include('common.forms.pullsettings', ['model' => $model])
        </div>
    </div>

@endcomponent