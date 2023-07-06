@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.npcimport.title')])

@section('header-title', __('views/admin.tools.npcimport.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.npcimport.submit']) }}
    <div class="form-group">
        {!! Form::label('import_string', __('views/admin.tools.npcimport.paste_npc_import_string')) !!}
        {{ Form::textarea('import_string', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('views/admin.tools.npcimport.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection