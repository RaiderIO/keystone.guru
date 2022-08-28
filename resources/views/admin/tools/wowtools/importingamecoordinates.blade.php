@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.wowtools.importingamecoordinates.title')])

@section('header-title', __('views/admin.tools.wowtools.importingamecoordinates.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.wowtools.import_ingame_coordinates.submit']) }}
    <div class="form-group">
        {!! Form::label('map_csv', __('views/admin.tools.wowtools.importingamecoordinates.map_csv')) !!}
        {{ Form::textarea('map_csv', '', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {!! Form::label('ui_map_group_member_csv', __('views/admin.tools.wowtools.importingamecoordinates.ui_map_group_member_csv')) !!}
        {{ Form::textarea('ui_map_group_member_csv', '', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {!! Form::label('ui_map_assignment_csv', __('views/admin.tools.wowtools.importingamecoordinates.ui_map_assignment_csv')) !!}
        {{ Form::textarea('ui_map_assignment_csv', '', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('views/admin.tools.wowtools.importingamecoordinates.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
