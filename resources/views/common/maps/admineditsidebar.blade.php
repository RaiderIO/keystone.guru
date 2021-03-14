<?php
/** @var \App\Models\Dungeon $dungeon */
/** @var \App\Models\Floor $floor */

$show = isset($show) ? $show : [];
$floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
// Set correctly displayed floor
?>
@include('common.general.inline', ['path' => 'common/maps/admineditsidebar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'sidebarSelector' => '#admineditsidebar',
    'sidebarScrollSelector' => '#admineditsidebar .sidebar-content',
    'sidebarToggleSelector' => '#admineditsidebarToggle',
    'switchDungeonFloorSelect' => '#map_floor_selection',
    'defaultSelectedFloorId' => $floor->id,
]])

@component('common.maps.sidebar', [
    'dungeon' => $dungeon,
    'header' => __('Admin toolbox'),
    'anchor' => 'left',
    'id' => 'admineditsidebar'
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
                    <div class="row view_dungeonroute_details_row">
                        <div class="col font-weight-bold">
                            {{ __('Floor') }}:
                        </div>
                    </div>
                    <div class="row view_dungeonroute_details_row mt-1">
                        <div class="col floor_selection">
                            <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                            {!! Form::select('map_floor_selection', [__('Select floor')], $floor->id, ['id' => 'map_floor_selection', 'class' => 'form-control selectpicker']) !!}
                        </div>
                    </div>
                @else
                    {!! Form::input('hidden', 'map_floor_selection', $floor->id, ['id' => 'map_floor_selection']) !!}
                @endif
            </div>
        </div>

        <!-- Mouseover enemy information -->
        <div id="enemy_info_container" class="form-group" style="display: none">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Enemy info') }}</h5>
                    <div id="enemy_info_key_value_container">

                    </div>
                </div>
            </div>
        </div>
    </div>
@endcomponent