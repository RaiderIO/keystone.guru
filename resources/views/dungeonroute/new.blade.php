@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

@section('scripts')
    @parent

    <script>
        // Defined in dungeonroutesetup.js
        $(function () {
            // Init
            _setStage(1);
        });
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset
    <div id="setup_container" class="container {{ isset($model) ? 'hidden' : '' }}">
        <div class="col-lg-12">
            <div class="col-lg-1">
                {!! Form::button('<i class="fa fa-backward"></i> ' . __('Previous'), ['id' => 'previous', 'class' => 'btn btn-info hidden']) !!}
            </div>
            <div class="col-lg-offset-10 col-lg-1">
                {!! Form::button('<i class="fa fa-forward"></i> ' . __('Next'), ['id' => 'next', 'class' => 'btn btn-info']) !!}
                {!! Form::submit('' . __('Finish'), ['id' => 'finish', 'class' => 'btn btn-success hidden']) !!}
            </div>
        </div>

        <hr/>

        <div id="stage-1" class="col-lg-12">
            <h2>
                {{ __('Dungeon') }}
            </h2>
            <div class="form-group">
                {!! Form::label('dungeon', __('Select dungeon') . "*") !!}
                {!! Form::select('dungeon', \App\Models\Dungeon::all()->pluck('name', 'id'), 0, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div id="stage-2" class="col-lg-12" style="display: none;">
            @include('common.group.composition')
        </div>
    </div>

    {!! Form::close() !!}
@endsection

