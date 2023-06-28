<?php
/** @var $npc \App\Models\Npc */
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_npc_enemy_forces_table').DataTable({
                'order': [[1, 'desc']]
            });
        });
    </script>
@endsection

<h4>{{ __('views/admin.npcenemyforces.title') }}</h4>
<table id="admin_npc_enemy_forces_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('views/admin.npcenemyforces.table_header_id') }}</th>
        <th width="40%">{{ __('views/admin.npcenemyforces.table_header_mapping_version') }}</th>
        <th width="10%">{{ __('views/admin.npcenemyforces.table_header_enemy_forces') }}</th>
        <th width="20%">{{ __('views/admin.npcenemyforces.table_header_actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($npc->npcEnemyForces()->with(['mappingVersion'])->get() as $npcEnemyForce)
        <tr>
            <td>{{ $npcEnemyForce->id }}</td>
            <td>{{ $npcEnemyForce->mappingVersion->getPrettyName() }}</td>
            <td>{{ $npcEnemyForce->enemy_forces }}</td>
            <td>
                @if($npcEnemyForce->mappingVersion->merged)
                    {{ __('Mapping version is read-only') }}
                @else
                    <a class="btn btn-info"
                       href="{{ route('admin.npcenemyforces.edit', ['npc' => $npc, 'npcEnemyForces' => $npcEnemyForce]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('views/admin.npcenemyforces.edit_enemy_forces') }}
                    </a>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
