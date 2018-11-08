<?php
/** @var \App\Models\DungeonRoute $model */

$show = isset($show) ? $show : [];
// May not be set in the case of a tryout version
if (isset($model)) {
    $dungeon = \App\Models\Dungeon::findOrFail($model->dungeon_id);
    $floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
}
?>

@section('sidebar-content')

    <!-- Visibility -->
    <div class="form-group visibility_tools">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Visibility') }}</h5>
                <div class="form-group">
                    <div class="row">
                        <div id="map_enemy_visuals_container" class="col">
                        </div>
                    </div>
                </div>

                @if($floorSelection)
                    <div class="form-group">
                        <div class="row view_dungeonroute_details_row">
                            <div class="col font-weight-bold">
                                {{ __('Floor') }}:
                            </div>
                        </div>
                        <div class="row view_dungeonroute_details_row mt-2">
                            <div class="col floor_selection">
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
@endsection

@include('common.maps.sidebar', ['header' => __('Infested voting')])