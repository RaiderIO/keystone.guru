<?php
/** @var $npc \App\Models\Npc */
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_npc_enemy_forces_table').DataTable({});
        });
    </script>
@endsection

<h4>{{ __('views/admin.npc.edit.enemyforces.title') }}</h4>
<table id="admin_npc_enemy_forces_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('views/admin.npc.edit.enemyforces.table_header_id') }}</th>
        <th width="40%">{{ __('views/admin.npc.edit.enemyforces.table_header_mapping_version') }}</th>
        <th width="10%">{{ __('views/admin.npc.edit.enemyforces.table_header_enemy_forces') }}</th>
        <th width="20%">{{ __('views/admin.npc.edit.enemyforces.table_header_actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($npc->npcEnemyForces as $npcEnemyForce)
        <tr>
            <td>{{ $npcEnemyForce->id }}</td>
            <td>{{ $npcEnemyForce->mappingVersion->getPrettyName() }}</td>
            <td>{{ $npcEnemyForce->enemy_forces }}</td>
            <td>
                @if($npcEnemyForce->mappingVersion->merged)
                    {{ __('Mapping version is read-only') }}
                @else
                    {{ __('Edit') }}
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
