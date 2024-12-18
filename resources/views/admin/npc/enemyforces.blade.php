<?php

use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;

/**
 * @var Npc $npc
 **/
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

<h4>{{ __('view_admin.npcenemyforces.title') }}</h4>
<table id="admin_npc_enemy_forces_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('view_admin.npcenemyforces.table_header_id') }}</th>
        <th width="40%">{{ __('view_admin.npcenemyforces.table_header_mapping_version') }}</th>
        <th width="10%">{{ __('view_admin.npcenemyforces.table_header_enemy_forces') }}</th>
        <th width="20%">{{ __('view_admin.npcenemyforces.table_header_actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($npc->npcEnemyForces()->with(['mappingVersion'])->get() as $npcEnemyForces)
            <?php /** @var NpcEnemyForces $npcEnemyForces */ ?>
        <tr>
            <td>{{ $npcEnemyForces->id }}</td>
            <td>{{ $npcEnemyForces->mappingVersion->getPrettyName() }}</td>
            <td>{{ $npcEnemyForces->enemy_forces }}</td>
            <td>
                @if($npcEnemyForces->mappingVersion->merged)
                    {{ __('view_admin.npcenemyforces.mapping_version_read_only') }}
                @else
                    <a class="btn btn-info"
                       href="{{ route('admin.npcenemyforces.edit', ['npc' => $npc, 'npcEnemyForces' => $npcEnemyForces]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.npcenemyforces.edit_enemy_forces') }}
                    </a>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
