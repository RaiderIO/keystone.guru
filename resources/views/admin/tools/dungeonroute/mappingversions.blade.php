<?php
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Models\Mapping\MappingVersion;

/**
 * @var EloquentCollection<int, MappingVersion> $unusedMappingVersions
 * @var EloquentCollection<int, MappingVersion> $usedMappingVersions
 */
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.dungeonroute.mappingversions.title')])

@section('header-title', __('view_admin.tools.dungeonroute.mappingversions.header'))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_route_mapping_versions_unused_table').DataTable({
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {})
            });
            $('#admin_dungeon_route_mapping_versions_used_table').DataTable({
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {})
            });
        });
    </script>
@endsection

@section('content')
    <h4>{{ __('view_admin.tools.dungeonroute.mappingversions.used_header') }}</h4>
    <p class="text-muted">{{ __('view_admin.tools.dungeonroute.mappingversions.used_description') }}</p>

    <div class="form-group">
        <table id="admin_dungeon_route_mapping_versions_used_table" class="tablesorter default_table table-striped">
            <thead>
            <tr>
                <th width="60%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_mapping_version_name') }}</th>
                <th width="20%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_count') }}</th>
                <th width="20%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($usedMappingVersions as $mappingVersion)
                <tr>
                    <td>{{ $mappingVersion->getPrettyName() }}</td>
                    <td>{{ $mappingVersion->dungeon_routes_count }}</td>
                    <td>
                        @if($mappingVersion->isLatestForDungeon())
                            <span class="badge badge-success">
                                {{ __('view_admin.tools.dungeonroute.mappingversions.action_is_latest') }}
                            </span>
                        @else
                            {{ html()->form('POST', route('admin.tools.dungeonroute.mappingversionusage.upgrade', ['mappingVersion' => $mappingVersion->id]))->open() }}
                            @csrf
                            {{ html()->input('submit')
                                ->value(__('view_admin.tools.dungeonroute.mappingversions.action_upgrade_all'))
                                ->class('btn btn-warning btn-sm')
                                ->attribute('onclick', sprintf("return confirm('%s')", __('view_admin.tools.dungeonroute.mappingversions.confirm_upgrade_all'))) }}
                            {{ html()->form()->close() }}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <h4 class="mt-4">{{ __('view_admin.tools.dungeonroute.mappingversions.unused_header') }}</h4>
    <p class="text-muted">{{ __('view_admin.tools.dungeonroute.mappingversions.unused_description') }}</p>

    <div class="form-group">
        <table id="admin_dungeon_route_mapping_versions_unused_table" class="tablesorter default_table table-striped">
            <thead>
            <tr>
                <th width="80%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_mapping_version_name') }}</th>
                <th width="20%">{{ __('view_admin.tools.dungeonroute.mappingversions.table_header_count') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($unusedMappingVersions as $mappingVersion)
                <tr>
                    <td>{{ $mappingVersion->getPrettyName() }}</td>
                    <td>{{ $mappingVersion->dungeon_routes_count }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
