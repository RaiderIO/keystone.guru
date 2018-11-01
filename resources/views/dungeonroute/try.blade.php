@extends('layouts.app', ['custom' => true, 'footer' => false, 'headerFloat' => true])
@section('header-title', $headerTitle)

@section('content')
    <?php if(!isset($dungeon_id)) { ?>
    {{ Form::open(['route' => 'dungeonroute.try.post']) }}
    <div class="container">
        <h3>
            {{ __('General') }}
        </h3>
        <div class="form-group">
            {!! Form::label('dungeon_id', __('Select dungeon') . "*") !!}
            {!! Form::select('dungeon_id', \App\Models\Dungeon::active()->pluck('name', 'id'), null, ['id' => 'dungeon_id_select', 'class' => 'form-control']) !!}
            <div id="siege_of_boralus_faction_warning" class="text-warning" style="display: none;">
                {{ __('Due to differences between the Horde and the Alliance version of Siege of Boralus, you are required to select a faction in the group composition.') }}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('teeming', __('Teeming (check to change the dungeon to resemble Teeming week)')) !!}
            {!! Form::checkbox('teeming', 1, 0, ['class' => 'form-control left_checkbox']) !!}
        </div>

        <div class="form-group">
            {!! Form::submit(__('Try it!'), ['class' => 'btn btn-info col-md-auto']) !!}
            <div class="col-md">

            </div>
        </div>
    </div>

    {!! Form::close() !!}
    <?php } else { ?>

    <div class="wrapper">
        @include('common.maps.sidebar', [
            'show' => [
                'no-modifications-warning'
            ]
        ])

        @include('common.maps.map', [
            'dungeon' => \App\Models\Dungeon::findOrFail($dungeon_id),
            'edit' => true
        ])
        <div class="form-group">
            <h4>
                {{ __('Need some help?') }}
            </h4>
            <div id="start_virtual_tour" class="btn btn-info col-md-auto">
                <i class="fas fa-info-circle"></i> {{ __('Start virtual tour') }}
            </div>
            <div class="col-md">

            </div>
        </div>
    </div>

    <?php } ?>
@endsection

