<?php
/** @var \App\Models\DungeonRoute $model */

$show = isset($show) ? $show : [];
// May not be set in the case of a tryout version
if (isset($model)) {
    $dungeon = \App\Models\Dungeon::findOrFail($model->dungeon_id);
    $floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
}
// Set correctly displayed floor
$floorId = isset($floorId) ? $floorId : $dungeon->floors[0]->id;
?>
@component('common.maps.sidebar', [
'header' => __('Admin toolbox'),
'anchor' => 'left',
'id' => 'admineditsidebar',
'selectedFloorId' => $floorId
])
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
                    <div class="row view_dungeonroute_details_row mt-1">
                        <div class="col floor_selection">
                            <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                            {!! Form::select('map_floor_selection', [__('Select floor')], $floorId, ['id' => 'map_floor_selection', 'class' => 'form-control selectpicker']) !!}
                        </div>
                    </div>
                @else
                    {!! Form::input('hidden', 'map_floor_selection', $floorId, ['id' => 'map_floor_selection']) !!}
                @endif
            </div>
        </div>
    </div>

    <!-- Floor settings -->
    <div class="form-group">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Floor settings') }}</h5>
                @isset($model)
                    {{ Form::model($model, ['route' => ['admin.floor.update', 'id' => $model->id], 'method' => 'patch']) }}
                @else
                    {{ Form::open(['route' => ['admin.floor.savenew', 'dungeon' => $dungeon->id]]) }}
                @endisset

                <div class="form-group{{ $errors->has('index') ? ' has-error' : '' }}">
                    {!! Form::label('index', __('Index'), ['class' => 'font-weight-bold']) !!}:
                    {!! Form::text('index', null, ['class' => 'form-control']) !!}
                    @include('common.forms.form-error', ['key' => 'index'])
                </div>

                <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                    {!! Form::label('name', __('Floor name'), ['class' => 'font-weight-bold']) !!}:
                    {!! Form::text('name', null, ['class' => 'form-control']) !!}
                    @include('common.forms.form-error', ['key' => 'name'])
                </div>

                <div class="form-group">
                    {!! Form::label('connectedfloors[]', __('Connected floors'), ['class' => 'font-weight-bold']) !!}:
                    {!! Form::select('connectedfloors[]', $floors->pluck('name', 'id'), isset($model) ? $model->connectedFloors()->pluck('id')->all() : null,
                        ['multiple' => 'multiple', 'class' => 'form-control']) !!}
                </div>

                {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

                {!! Form::close() !!}
            </div>
        </div>
    </div>
@endcomponent