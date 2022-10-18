<?php
/** @var $dungeon \App\Models\Dungeon */
?>

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_speedrun_required_npcs_table').DataTable({});
        });
    </script>
@endsection

<h4>{{ __('views/admin.dungeon.edit.speedrun_required_npcs.title') }}</h4>
<div class="float-right">
    <a href="{{ route('admin.dungeonspeedrunrequirednpc.new', ['dungeon' => $dungeon->slug]) }}"
       class="btn btn-success text-white pull-right" role="button">
        <i class="fas fa-plus"></i> {{ __('views/admin.dungeon.edit.speedrun_required_npcs.add_npc') }}
    </a>
</div>

<table id="admin_dungeon_speedrun_required_npcs_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('views/admin.dungeon.edit.speedrun_required_npcs.table_header_id') }}</th>
        <th width="70%">{{ __('views/admin.dungeon.edit.speedrun_required_npcs.table_header_npc') }}</th>
        <th width="10%">{{ __('views/admin.dungeon.edit.speedrun_required_npcs.table_header_count') }}</th>
        <th width="10%">{{ __('views/admin.dungeon.edit.speedrun_required_npcs.table_header_actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($dungeon->dungeonspeedrunrequirednpcs as $speedrunRequiredNpc)
        <tr>
            <td>{{ $speedrunRequiredNpc->id }}</td>
            <td>{{ sprintf('%s (%d)', $speedrunRequiredNpc->npc->name, $speedrunRequiredNpc->npc->id) }}</td>
            <td>{{ $speedrunRequiredNpc->count }}</td>
            <td>
                <a class="btn btn-danger"
                   href="{{ route('admin.dungeonspeedrunrequirednpc.delete', ['dungeon' => $dungeon->slug, 'dungeonspeedrunrequirednpc' => $speedrunRequiredNpc->id]) }}">
                    <i class="fas fa-trash"></i>&nbsp;{{ __('views/admin.dungeon.edit.speedrun_required_npcs.npc_delete') }}
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
