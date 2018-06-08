@extends('layouts.app', ['wide' => isset($model)])
@section('header-title')
    {{ $headerTitle }}
    <a href="{{ route('admin.dungeon.edit', ['id' => $dungeon->id]) }}" class="btn btn-info text-white pull-right"
       role="button">
        <i class="fa fa-backward"></i> {{ __('Back to dungeon') }}
    </a>
@endsection
<?php
/**
 * @var $model \App\Models\Floor
 * @var $dungeon \App\Models\Dungeon
 * @var $floors \Illuminate\Support\Collection
 */
?>
@section('scripts')
    <script>
        var _switchDungeonFloorSelect = "#map_floor_selection";
        $(function () {
            $(_switchDungeonFloorSelect).change(function () {
                _refreshMap();
            });
            _refreshMap();
            adminInitControls(mapObj);
        });

        function _refreshMap() {
            setCurrentMapName("{{ strtolower(str_replace(" ", "", $dungeon->name)) }}", $(_switchDungeonFloorSelect).val());
        }
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.floor.update', 'id' => $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => ['admin.floor.savenew', 'dungeon' => $dungeon->id]]) }}
    @endisset

    <div class="form-group">
        {!! Form::label('dungeon', __('Dungeon')) !!}
        {!! Form::select('dungeon', [$dungeon->id => $dungeon->name], null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
    </div>

    <div class="form-group{{ $errors->has('index') ? ' has-error' : '' }}">
        {!! Form::label('index', __('Index')) !!}
        {!! Form::text('index', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'index'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('Floor name')) !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group">
        {!! Form::label('connectedfloors[]', __('Connected floors')) !!}
        {!! Form::select('connectedfloors[]', $floors->pluck('name', 'id'), isset($model) ? $model->connectedFloors()->pluck('id')->all() : null,
            ['multiple' => 'multiple', 'class' => 'form-control']) !!}
    </div>

    @isset($model)
        <h3>Enemy placement</h3>
        <div class="form-group">
            {!! Form::label('map_floor_selection', __('Select floor')) !!}
            {!! Form::select('map_floor_selection', $dungeon->floors()->pluck('name', 'index'), [$model->index], ['class' => 'form-control']) !!}
        </div>

        <div class="form-group">
            <div id="map" class="col-md-10"></div>
            <div id="map-controls" class="col-md-2">
                <div class="panel panel-default">
                    <div class="panel-heading">{{ __("Map controls") }}</div>
                    <div class="panel-body">
                        <div>
                            {{ __("Enemies") }}
                        </div>
                        <div class="form-group">
                            {!! Form::button('<i class="fa fa-plus"></i> ' . __('Add enemy pack'), ['class' => 'btn btn-success']) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::button('<i class="fa fa-plus"></i> ' .__('Add enemy to pack'), ['class' => 'btn btn-success']) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endisset

    {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}

@endsection
