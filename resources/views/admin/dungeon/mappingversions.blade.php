<?php
/** @var $dungeon \App\Models\Dungeon */
/** @var $hasUnmergedMappingVersion bool */
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_mapping_versions_table').DataTable({
                'order': [[1, 'desc']]
            });
        });
    </script>
@endsection

<h4>{{ __('view_admin.dungeon.edit.mapping_versions.title') }}</h4>
{{--@if(!$hasUnmergedMappingVersion)--}}
<div class="float-right">
    <a href="{{ route('admin.mappingversion.new', ['dungeon' => $dungeon->slug]) }}"
       class="btn btn-success text-white pull-right" role="button">
        <i class="fas fa-plus"></i> {{ __('view_admin.dungeon.edit.mapping_versions.add_mapping_version') }}
    </a>
</div>
{{--@endif--}}

<table id="admin_dungeon_mapping_versions_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header_merged') }}</th>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header_id') }}</th>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header_version') }}</th>
        <th width="50%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header_created_at') }}</th>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header_actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($dungeon->mappingVersions as $mappingVersion)
        <tr>
            <td>
                <i class="fas {{ $mappingVersion->merged ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }}"></i>
            </td>
            <td>{{ $mappingVersion->id }}</td>
            <td>{{ $mappingVersion->version }}</td>
            <td>{{ $mappingVersion->created_at->toDateTimeString() }}</td>
            <td>
                <a class="btn btn-danger"
                   href="{{ route('admin.mappingversion.delete', ['dungeon' => $dungeon->slug, 'mappingVersion' => $mappingVersion]) }}">
                    <i class="fas fa-trash"></i>&nbsp;{{ __('view_admin.dungeon.edit.mapping_versions.delete') }}
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
