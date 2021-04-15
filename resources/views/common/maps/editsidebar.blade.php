<?php
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */

$show = isset($show) ? $show : [];
$showVirtualTour = $show['virtual-tour'] ?? false;
$showSandbox = $show['sandbox'] ?? true;
// May not be set in the case of a sandbox version
if (isset($model)) {
    $floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
}
?>
@include('common.general.inline', ['path' => 'common/maps/editsidebar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'sidebarSelector' => '#editsidebar',
    'sidebarScrollSelector' => '#editsidebar .sidebar-content',
    'sidebarToggleSelector' => '#editsidebarToggle',
    'switchDungeonFloorSelect' => '#map_floor_selection',
    'defaultSelectedFloorId' => $floorId,
    'anchor' => 'left'
]])

@component('common.maps.sidebar', [
    'dungeon' => $dungeon,
    'header' => $model->title,
    'anchor' => 'left',
    'id' => 'editsidebar',
    'show' => $show,
])
    <!-- Visibility -->
    <div class="form-group visibility_tools">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Visibility') }}</h5>
                <div class="row">
                    <div class="col">
                        <div class="leaflet-draw-section">
                            <div id="map_enemy_visuals" class="form-group">
                                <div class="font-weight-bold">{{ __('Enemy display type') }}:</div>
                                <div id="map_enemy_visuals_container">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($floorSelection)
                    <div id="map_floor_selection_container">
                        <div class="row view_dungeonroute_details_row mt-3">
                            <div class="col font-weight-bold">
                                {{ __('Floor') }}:
                            </div>
                        </div>
                        <div class="row view_dungeonroute_details_row">
                            <div class="col">
                                <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                                {!! Form::select('map_floor_selection', [__('Select floor')], 1, ['id' => 'map_floor_selection', 'class' => 'form-control selectpicker']) !!}
                            </div>
                        </div>
                    </div>
                @else
                    {!! Form::input('hidden', 'map_floor_selection', $dungeon->floors[0]->id, ['id' => 'map_floor_selection']) !!}
                @endif
            </div>
        </div>
    </div>

    <!-- Actions -->
    @if( $showVirtualTour || $showSandbox)
    <div class="form-group route_actions">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Actions') }}</h5>

                @if($showVirtualTour)
                    <div class="form-group">
                        <!-- Virtual tour -->
                        <div class="row">
                            <div class="col">
                                <button id="start_tutorial" class="btn btn-info col">
                                    <i class="fas fa-info-circle"></i> {{ __('Start tutorial') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($showSandbox)
                    @guest
                        <div class="form-group">
                            <div id="map_login_and_continue">
                                <button class="btn btn-primary mt-1 w-100" data-toggle="modal"
                                        data-target="#login_modal">
                                    <i class="fas fa-sign-in-alt"></i> {{__('Login')}}
                                </button>
                            </div>
                            <div id="map_register_and_continue">
                                <button class="btn btn-primary mt-1 w-100" data-toggle="modal"
                                        data-target="#register_modal">
                                    <i class="fas fa-user-plus"></i> {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <span class="text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ sprintf(__('This temporary route expires on %s.'), \Illuminate\Support\Carbon::parse($model->expires_at)->format('Y-m-d at H:i')) }}
                            </span>
                            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                            __('A temporary route will exist for up to 24 hours before it is automatically removed. If you login or register, you can save the route to your profile if you wish to keep it. It will then no longer be scheduled for removal.')
                             }}"></i>
                        </div>
                    @else
                        <div id="map_save_and_continue" class="form-group">
                            <a href="{{ route('dungeonroute.claim', ['dungeonroute' => $model->public_key]) }}"
                               class="btn btn-primary mt-1 w-100" role="button">
                                <i class="fas fa-save"></i> {{ __('Save to profile') }}
                            </a>
                        </div>
                        <div class="form-group">
                            <span class="text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ sprintf(__('This temporary route expires on %s.'), \Illuminate\Support\Carbon::parse($model->expires_at)->format('Y-m-d at H:i')) }}
                            </span>
                            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                            __('A temporary route will exist for up to 24 hours before it is automatically removed. You can save the route to your profile if you wish to keep it, it will then no longer be scheduled for removal.')
                             }}"></i>
                        </div>
                    @endguest
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Mouseover enemy information -->
    <div id="enemy_info_container" class="form-group" style="display: none">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Enemy info') }}</h5>
                <div id="enemy_info_key_value_container">

                </div>
                <div class="row mt-2">
                    <div class="col">
                        <a href="#" data-toggle="modal"
                           data-target="#userreport_enemy_modal">
                            <button class="btn btn-warning w-100">
                                <i class="fa fa-bug"></i>
                                {{ __('Report an issue') }}
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcomponent