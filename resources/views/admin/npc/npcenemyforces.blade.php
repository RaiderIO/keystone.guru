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
                'order': [[1, 'desc']],
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

                })
            });
        });
    </script>
@endsection

<div class="row">
    <div class="col">
        <h4>{{ __('view_admin.npcenemyforces.title') }}</h4>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.npc.npcenemyforces.new', ['npc' => $npc]) }}"
           class="btn btn-success text-white pull-right" role="button">
            <i class="fas fa-plus"></i> {{ __('view_admin.npc.npcenemyforces.add_npc_enemy_forces') }}
        </a>
    </div>
</div>
<table id="admin_npc_enemy_forces_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('view_admin.npc.npcenemyforces.table_header_id') }}</th>
        <th width="55%">{{ __('view_admin.npc.npcenemyforces.table_header_mapping_version') }}</th>
        <th width="20%">{{ __('view_admin.npc.npcenemyforces.table_header_enemy_forces') }}</th>
        <th width="15%">{{ __('view_admin.npc.npcenemyforces.table_header_actions') }}</th>
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
                    {{ __('view_admin.npc.npcenemyforces.mapping_version_read_only') }}
                @else
                    <div class="row no-gutters">
                        <div class="col">
                            <a class="btn btn-info"
                               href="{{ route('admin.npc.npcenemyforces.edit', ['npc' => $npc, 'npcEnemyForces' => $npcEnemyForces]) }}">
                                <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.npc.npcenemyforces.edit_npc_enemy_forces') }}
                            </a>
                        </div>
                        <div class="col">
                            {{ html()->form('POST', route('admin.npc.npcenemyforces.delete', ['npc' => $npc, 'npcEnemyForces' => $npcEnemyForces]))->open() }}
                            {{ html()->hidden('_method', 'delete') }}
                            {{ html()->input('submit')->value(__('view_admin.npc.npcenemyforces.delete_npc_enemy_forces'))->class('btn btn-danger')->name('submit') }}
                            {{ html()->form()->close() }}
                        </div>
                    </div>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
