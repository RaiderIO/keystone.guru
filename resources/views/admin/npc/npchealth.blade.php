<?php

use App\Models\Npc\Npc;
use App\Models\Npc\npchealth;

/**
 * @var Npc $npc
 **/
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_npc_npc_health_table').DataTable({
                'order': [[1, 'asc']]
            });
        });
    </script>
@endsection

<div class="row">
    <div class="col">
        <h4>{{ __('view_admin.npchealth.title') }}</h4>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.npchealth.new', ['npc' => $npc]) }}"
           class="btn btn-success text-white pull-right" role="button">
            <i class="fas fa-plus"></i> {{ __('view_admin.npc.npchealth.add_npc_health') }}
        </a>
    </div>
</div>

<table id="admin_npc_npc_health_table" class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('view_admin.npc.npchealth.table_header.id') }}</th>
        <th width="50%">{{ __('view_admin.npc.npchealth.table_header.game_version') }}</th>
        <th width="10%">{{ __('view_admin.npc.npchealth.table_header.health') }}</th>
        <th width="10%">{{ __('view_admin.npc.npchealth.table_header.percentage') }}</th>
        <th width="20%">{{ __('view_admin.npc.npchealth.table_header.actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($npc->npcHealths()->with(['gameVersion'])->get() as $npcHealth)
            <?php /** @var NpcHealth $npcHealth */ ?>
        <tr>
            <td>{{ $npcHealth->id }}</td>
            <td>{{ __($npcHealth->gameVersion->name) }}</td>
            <td>{{ $npcHealth->health }}</td>
            <td>{{ $npcHealth->percentage }}</td>
            <td>
                <div class="row no-gutters">
                    <div class="col">
                        <a class="btn btn-info"
                           href="{{ route('admin.npchealth.edit', ['npc' => $npc, 'npcHealth' => $npcHealth]) }}">
                            <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.npc.npchealth.edit_npc_health') }}
                        </a>
                    </div>
                    <div class="col">
                        {{ Form::open(['route' => ['admin.npchealth.delete', 'npc' => $npc, 'npcHealth' => $npcHealth]]) }}
                        {!! Form::hidden('_method', 'delete') !!}
                        {!! Form::submit(__('view_admin.npc.npchealth.delete_npc_health'), ['class' => 'btn btn-danger', 'name' => 'submit']) !!}
                        {!! Form::close() !!}
                    </div>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
