<?php
/** @var $dungeon \App\Models\Dungeon */
?>

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_floor_table').DataTable({});
        });
    </script>
@endsection

<h4>{{ __('views/admin.dungeon.edit.floor_management.title') }}</h4>
<div class="float-right">
    <a href="{{ route('admin.floor.new', ['dungeon' => $dungeon->slug]) }}"
       class="btn btn-success text-white pull-right" role="button">
        <i class="fas fa-plus"></i> {{ __('views/admin.dungeon.edit.floor_management.add_floor') }}
    </a>
</div>

<table id="admin_dungeon_floor_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('views/admin.dungeon.edit.floor_management.table_header_id') }}</th>
        <th width="10%">{{ __('views/admin.dungeon.edit.floor_management.table_header_index') }}</th>
        <th width="60%">{{ __('views/admin.dungeon.edit.floor_management.table_header_name') }}</th>
        <th width="20%">{{ __('views/admin.dungeon.edit.floor_management.table_header_actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($dungeon->floors as $floor)
        <tr>
            <td>{{ $floor->id }}</td>
            <td>{{ $floor->index }}</td>
            <td>{{ __($floor->name) }}</td>
            <td>
                <a class="btn btn-primary"
                   href="{{ route('admin.floor.edit', ['dungeon' => $dungeon->slug, 'floor' => $floor->id]) }}">
                    <i class="fas fa-edit"></i>&nbsp;{{ __('views/admin.dungeon.edit.floor_management.floor_edit_edit') }}
                </a>
                <a class="btn btn-primary"
                   href="{{ route('admin.floor.edit.mapping', ['dungeon' => $dungeon->slug, 'floor' => $floor->id]) }}">
                    <i class="fas fa-route"></i>&nbsp;{{ __('views/admin.dungeon.edit.floor_management.floor_edit_mapping') }}
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
