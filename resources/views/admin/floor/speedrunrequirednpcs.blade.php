<?php

use App\Models\Dungeon;
use App\Models\Floor\Floor;

/**
 * @var  int     $difficulty
 * @var  Dungeon $dungeon
 * @var  Floor   $floor
 */
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_speedrun_required_npcs_{{ $difficulty }}_table').DataTable({});
        });
    </script>
@endsection

<h4>
    @if($difficulty === Dungeon::DIFFICULTY_10_MAN )
        {{ __('view_admin.floor.edit.speedrun_required_npcs.title_10_man') }}
    @else
        {{ __('view_admin.floor.edit.speedrun_required_npcs.title_25_man') }}
    @endif
</h4>
<div class="float-right">
    <a href="{{ route('admin.dungeonspeedrunrequirednpc.new', ['dungeon' => $dungeon, 'floor' => $floor, 'difficulty' => $difficulty]) }}"
       class="btn btn-success text-white pull-right" role="button">
        <i class="fas fa-plus"></i> {{ __('view_admin.floor.edit.speedrun_required_npcs.add_npc') }}
    </a>
</div>

<table id="admin_dungeon_speedrun_required_npcs_{{ $difficulty }}_table"
       class="tablesorter default_table table-striped">
    <thead>
    <tr>
        <th width="10%">{{ __('view_admin.floor.edit.speedrun_required_npcs.table_header_id') }}</th>
        <th width="70%">{{ __('view_admin.floor.edit.speedrun_required_npcs.table_header_npc') }}</th>
        <th width="10%">{{ __('view_admin.floor.edit.speedrun_required_npcs.table_header_count') }}</th>
        <th width="10%">{{ __('view_admin.floor.edit.speedrun_required_npcs.table_header_actions') }}</th>
    </tr>
    </thead>

    <tbody>
    <?php
    $speedrunRequiredNpcs = $difficulty === Dungeon::DIFFICULTY_10_MAN ?
        $floor->dungeonSpeedrunRequiredNpcs10Man : $floor->dungeonSpeedrunRequiredNpcs25Man;
    ?>
    @foreach ($speedrunRequiredNpcs as $speedrunRequiredNpc)
        <tr>
            <td>{{ $speedrunRequiredNpc->id }}</td>
            <td>{{ $speedrunRequiredNpc->getDisplayText() }}</td>
            <td>{{ $speedrunRequiredNpc->count }}</td>
            <td>
                <a class="btn btn-danger"
                   href="{{
                        route('admin.dungeonspeedrunrequirednpc.delete', [
                            'dungeon' => $dungeon,
                            'floor' => $floor,
                            'dungeonspeedrunrequirednpc' => $speedrunRequiredNpc->id,
                            'difficulty' => $difficulty,
                        ])
                        }}">
                    <i class="fas fa-trash"></i>&nbsp;{{ __('view_admin.floor.edit.speedrun_required_npcs.npc_delete') }}
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>

</table>
