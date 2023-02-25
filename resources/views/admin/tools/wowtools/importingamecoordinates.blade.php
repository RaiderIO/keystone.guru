@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.wowtools.importingamecoordinates.title')])

@section('header-title', __('views/admin.tools.wowtools.importingamecoordinates.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.wowtools.import_ingame_coordinates.submit']) }}
    <div class="form-group">
        {!! Form::label('map_table_xhr_response', __('views/admin.tools.wowtools.importingamecoordinates.map_table_xhr_response')) !!}
        {{ Form::textarea('map_table_xhr_response', '', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {!! Form::label('ui_map_group_member_table_xhr_response', __('views/admin.tools.wowtools.importingamecoordinates.ui_map_group_member_table_xhr_response')) !!}
        {{ Form::textarea('ui_map_group_member_table_xhr_response', '', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {!! Form::label('ui_map_assignment_table_xhr_response', __('views/admin.tools.wowtools.importingamecoordinates.ui_map_assignment_table_xhr_response')) !!}
        {{ Form::textarea('ui_map_assignment_table_xhr_response', '', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('views/admin.tools.wowtools.importingamecoordinates.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
