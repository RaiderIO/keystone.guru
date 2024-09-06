@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.npc.import.title')])

@section('header-title', __('view_admin.tools.npc.import.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.npc.import.submit']) }}
    <div class="form-group">
        {!! Form::label('import_string', __('view_admin.tools.npc.import.paste_npc_import_string')) !!}
        {{ Form::textarea('import_string', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('view_admin.tools.npc.import.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
