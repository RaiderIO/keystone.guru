<?php
use App\Models\Dungeon;

/**
 * @var  Dungeon      $dungeon
 * @var  bool $hasUnmergedMappingVersion
 */
?>

@section('scripts')
    @parent

    <!--suppress HtmlDeprecatedAttribute -->
    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_mapping_versions_table').DataTable({
                'order': [[1, 'desc']]
            });
        });
    </script>
@endsection

<div class="row">
    <div class="col">
        <h4>{{ __('view_admin.dungeon.edit.mapping_versions.title') }}</h4>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.mappingversion.new', ['dungeon' => $dungeon->slug]) }}"
           class="btn btn-success text-white pull-right" role="button">
            <i class="fas fa-plus"></i> {{ __('view_admin.dungeon.edit.mapping_versions.add_mapping_version') }}
        </a>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.mappingversion.newbare', ['dungeon' => $dungeon->slug]) }}"
           class="btn btn-success text-white pull-right" role="button">
            <i class="fas fa-plus"></i> {{ __('view_admin.dungeon.edit.mapping_versions.add_bare_mapping_version') }}
        </a>
    </div>
</div>

<table id="admin_dungeon_mapping_versions_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header.merged') }}</th>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header.facade') }}</th>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header.id') }}</th>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header.game_version') }}</th>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header.version') }}</th>
        <th width="50%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header.created_at') }}</th>
        <th width="10%">{{ __('view_admin.dungeon.edit.mapping_versions.table_header.actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($dungeon->loadMappingVersions()->mappingVersions as $mappingVersion)
        <tr>
            <td>
                <i class="fas {{ $mappingVersion->merged ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }}"></i>
            </td>
            <td>
                <i class="fas {{ $mappingVersion->facade_enabled ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }}"></i>
            </td>
            <td>{{ $mappingVersion->id }}</td>
            <td data-order="{{ $mappingVersion->game_version_id }}">
                <img src="{{ ksgAssetImage(sprintf('gameversions/%s.png', $mappingVersion->gameVersion->key)) }}"
                     alt="{{ __($mappingVersion->gameVersion->name) }}"
                     title="{{ __($mappingVersion->gameVersion->name) }}"
                     data-toggle="tooltip"
                     style="width: 50px;"/>
            </td>
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
