@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.wowtools.importingamecoordinates.title')])

@section('header-title', __('view_admin.tools.wowtools.importingamecoordinates.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.wowtools.import_ingame_coordinates.submit'))->open() }}
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.wowtools.importingamecoordinates.map_table_xhr_response'), 'map_table_xhr_response') }}
        {{ html()->textarea('map_table_xhr_response', '')->class('form-control') }}
    </div>
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.wowtools.importingamecoordinates.ui_map_group_member_table_xhr_response'), 'ui_map_group_member_table_xhr_response') }}
        {{ html()->textarea('ui_map_group_member_table_xhr_response', '')->class('form-control') }}
    </div>
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.wowtools.importingamecoordinates.ui_map_assignment_table_xhr_response'), 'ui_map_assignment_table_xhr_response') }}
        {{ html()->textarea('ui_map_assignment_table_xhr_response', '')->class('form-control') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.wowtools.importingamecoordinates.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
