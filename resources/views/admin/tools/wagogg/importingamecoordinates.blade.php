@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.wagogg.importingamecoordinates.title')])

@section('header-title', __('view_admin.tools.wagogg.importingamecoordinates.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.wagogg.import_ingame_coordinates.submit'))->open() }}
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.wagogg.importingamecoordinates.ui_map_assignment_table_csv'), 'ui_map_assignment_table_csv') }}
        {{ html()->textarea('ui_map_assignment_table_csv', '')->class('form-control') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.wagogg.importingamecoordinates.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
