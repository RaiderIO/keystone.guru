@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.enemyforces.title')])

@section('header-title', __('views/admin.tools.enemyforces.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.enemyforces.import.submit']) }}
    <div class="form-group">
        {!! Form::label('import_string', __('views/admin.tools.enemyforces.paste_mennos_export_json')) !!}
        {{ Form::textarea('import_string', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('views/admin.tools.enemyforces.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
