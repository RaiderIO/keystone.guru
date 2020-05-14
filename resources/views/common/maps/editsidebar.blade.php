<?php
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */

$show = isset($show) ? $show : [];
// May not be set in the case of a tryout version
if (isset($model)) {
    $floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
}
?>
@include('common.general.inline', ['path' => 'common/maps/editsidebar', 'options' => [
    'sidebarSelector' => '#editsidebar',
    'sidebarScrollSelector' => '#editsidebar .sidebar-content',
    'sidebarToggleSelector' => '#editsidebarToggle',
    'switchDungeonFloorSelect' => '#map_floor_selection',
    'defaultSelectedFloorId' => $dungeon->floors[0]->id,
    'anchor' => 'left',
]])

@component('common.maps.sidebar', [
    'header' => __('Toolbox'),
    'anchor' => 'left',
    'id' => 'editsidebar'
])

    @isset($show['shareable-link'])
        <!-- Shareable link -->
        <div class="form-group">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Shareable link') }}</h5>
                    <div class="row">
                        <div class="input-group-append">
                            {!! Form::text('map_shareable_link', route('dungeonroute.view', ['dungeonroute' => $model->public_key]),
                            ['id' => 'map_shareable_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                            <div class="input-group-append">
                                <button id="map_copy_to_clipboard" class="btn btn-info"
                                        data-toggle="tooltip" title="{{ __('Copy to clipboard') }}">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endisset

    <!-- Visibility -->
    <div class="form-group visibility_tools">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Visibility') }}</h5>
                <div class="row">
                    <div id="map_enemy_visuals_container" class="col">
                    </div>
                </div>

                @if($floorSelection)
                    <div class="row view_dungeonroute_details_row">
                        <div class="col font-weight-bold">
                            {{ __('Floor') }}:
                        </div>
                    </div>
                    <div class="row view_dungeonroute_details_row">
                        <div class="col floor_selection">
                            <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                            {!! Form::select('map_floor_selection', [__('Select floor')], 1, ['id' => 'map_floor_selection', 'class' => 'form-control selectpicker']) !!}
                        </div>
                    </div>
                @else
                    {!! Form::input('hidden', 'map_floor_selection', $dungeon->floors[0]->id, ['id' => 'map_floor_selection']) !!}
                @endif
            </div>
        </div>
    </div>

    <!-- Draw Settings -->
    <div class="form-group draw_settings_tools">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Draw Settings') }}</h5>
                <?php // Weight ?>
                <div class="row view_dungeonroute_details_row">
                    <div class="col font-weight-bold">
                        {{ __('Line weight') }}:
                    </div>
                    <div class="col font-weight-bold">
                        {{ __('Color') }}:
                    </div>
                </div>
                <div class="row view_dungeonroute_details_row">
                    <div class="col line_weight_selection">
                        <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                        {!! Form::select('edit_route_freedraw_options_weight', [1, 2, 3, 4, 5],
                            isset($_COOKIE['polyline_default_weight']) ? $_COOKIE['polyline_default_weight'] : 0,
                            ['id' => 'edit_route_freedraw_options_weight', 'class' => 'form-control selectpicker']) !!}
                    </div>
                    <div class="col default_color_selection">
                        <div id="edit_route_freedraw_options_color">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="form-group route_actions">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Actions') }}</h5>

                @isset($show['virtual-tour'])
                    <div class="form-group">
                        <!-- Virtual tour -->
                        <div class="row">
                            <div class="col">
                                <button id="start_virtual_tour" class="btn btn-info col">
                                    <i class="fas fa-info-circle"></i> {{ __('Start virtual tour') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endisset

                @isset($show['route-settings'])
                    <div class="form-group">
                        <!-- Route settings -->
                        <div class="row">
                            <div class="col">
                                <button class="btn btn-info col" data-toggle="modal" data-target="#settings_modal">
                                    <i class='fas fa-cog'></i> {{ __('Route settings') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endisset


                @isset($show['route-publish'])
                <!-- Published state -->
                    <div class="form-group">
                        <div class="row">
                            <div class="col">
                                <button id="map_route_publish"
                                        class="btn btn-success col-md {{ $model->published === 1 ? 'd-none' : '' }}">
                                    <i class="fa fa-plane-departure"></i> {{ __('Publish route') }}
                                </button>
                                <button id="map_route_unpublish"
                                        class="btn btn-warning col-md {{ $model->published === 0 ? 'd-none' : '' }}">
                                    <i class="fa fa-plane-arrival"></i> {{ __('Unpublish route') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endisset

                @isset($show['tryout'])
                    @if (Auth::guest())
                        <div class="form-group">
                            <button class="btn btn-primary mt-1 w-100" data-toggle="modal" data-target="#login_modal">
                                <i class="fas fa-sign-in-alt"></i> {{__('Login and continue')}}
                            </button>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary mt-1 w-100" data-toggle="modal"
                                    data-target="#register_modal">
                                <i class="fas fa-user-plus"></i> {{ __('Register and continue') }}
                            </button>
                        </div>
                    @else
                        <div class="form-group">
                            <a href="{{ route('dungeonroute.edit', ['dungeonroute' => $model->public_key]) }}"
                               class="btn btn-primary mt-1 w-100" role="button">
                                <i class="fas fa-save"></i> {{ __('Save and continue') }}
                            </a>
                        </div>
                    @endif
                @endisset
            </div>
        </div>
    </div>

    @isset($show['route-publish'])
        <div class="form-group">
            <div class="row">
                <div class="col">
                    <div id="map_route_unpublished_info"
                         class="alert alert-info text-center {{ $model->published === 1 ? 'd-none' : '' }}">
                        <i class="fa fa-info-circle"></i> {{ __('Your route is currently unpublished. Nobody can view your route until you publish it.') }}
                    </div>
                </div>
            </div>
        </div>
    @endisset
@endcomponent