<?php
/** @var $mappingVersionUsage \Illuminate\Support\Collection */
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.dungeonroute.mappingversions.title')])

@section('header-title', __('view_admin.tools.dungeonroute.mappingversions.header'))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_route_mapping_versions_unused_table').DataTable({});
            $('#admin_dungeon_route_mapping_versions_used_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    <div class="form-group">
        <table id="admin_dungeon_route_mapping_versions_unused_table" class="tablesorter default_table table-striped">
            <thead>
            <tr>
                <th width="40%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_mapping_version_name') }}</th>
                <th width="40%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_count') }}</th>
                <th width="20%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($mappingVersionUsage['unused'] as $mappingVersionName => $count)
                <tr>
                    <td>{{ $mappingVersionName }}</td>
                    <td>{{ $count }}</td>
                    <td>Actions</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <table id="admin_dungeon_route_mapping_versions_used_table" class="tablesorter default_table table-striped">
            <thead>
            <tr>
                <th width="40%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_mapping_version_name') }}</th>
                <th width="40%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_count') }}</th>
                <th width="20%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($mappingVersionUsage['used'] as $mappingVersionName => $count)
                <tr>
                    <td>{{ $mappingVersionName }}</td>
                    <td>{{ $count }}</td>
                    <td>Actions</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
