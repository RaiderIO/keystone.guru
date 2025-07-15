@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.wagogg.importingamecoordinates.title')])

@section('header-title', __('view_admin.tools.wagogg.importingamecoordinates.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.wagogg.import_ingame_coordinates.submit']) }}
    <div class="form-group">
        {!! Form::label('ui_map_assignment_table_csv', __('view_admin.tools.wagogg.importingamecoordinates.ui_map_assignment_table_csv')) !!}
        {{ Form::textarea('ui_map_assignment_table_csv', '', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('view_admin.tools.wagogg.importingamecoordinates.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
