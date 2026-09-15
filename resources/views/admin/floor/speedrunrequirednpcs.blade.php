<?php

use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
use App\Models\Floor\Floor;

/**
 * @var Dungeon $dungeon
 * @var Floor   $floor
 */

$npcsByDifficulty     = $floor->dungeonSpeedrunRequiredNpcs->groupBy('difficulty');
$difficultiesWithData = array_filter(
    DungeonDifficulty::cases(),
    static fn(DungeonDifficulty $difficulty) => $npcsByDifficulty->has($difficulty->value),
);
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            let $tables = $('.admin_speedrun_required_npcs_table');
            $tables.DataTable({
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {})
            });

            // Recalculate column widths when a tab becomes visible, otherwise DataTables misaligns headers
            $('#admin_speedrun_required_npcs_tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
                $tables.DataTable().columns.adjust();
            });
        });
    </script>
@endsection

<div class="row">
    <div class="col">
        <h4>{{ __('view_admin.floor.edit.speedrun_required_npcs.title') }}</h4>
    </div>
    <div class="col-auto">
        <div class="dropdown">
            <button class="btn btn-success text-white dropdown-toggle" type="button"
                    id="admin_speedrun_required_npcs_add_dropdown" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-plus"></i> {{ __('view_admin.floor.edit.speedrun_required_npcs.add_npc') }}
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="admin_speedrun_required_npcs_add_dropdown">
                @foreach (DungeonDifficulty::cases() as $difficulty)
                    <a class="dropdown-item"
                       href="{{ route('admin.dungeonspeedrunrequirednpc.new', ['dungeon' => $dungeon, 'floor' => $floor, 'difficulty' => $difficulty->value]) }}">
                        {{ __('view_admin.floor.edit.speedrun_required_npcs.add_npc_for', ['difficulty' => $difficulty->translatedName()]) }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if (empty($difficultiesWithData))
    <p class="text-muted">{{ __('view_admin.floor.edit.speedrun_required_npcs.no_npcs') }}</p>
@else
    <ul id="admin_speedrun_required_npcs_tabs" class="nav nav-tabs" role="tablist">
        @foreach ($difficultiesWithData as $difficulty)
            <li class="nav-item">
                <a id="admin_speedrun_required_npcs_{{ $difficulty->slug() }}_tab"
                   class="nav-link {{ $loop->first ? 'active' : '' }}"
                   href="#admin_speedrun_required_npcs_{{ $difficulty->slug() }}_content"
                   role="tab"
                   aria-controls="admin_speedrun_required_npcs_{{ $difficulty->slug() }}_content"
                   aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                   data-bs-toggle="tab">
                    {{ $difficulty->translatedName() }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($difficultiesWithData as $difficulty)
            <div id="admin_speedrun_required_npcs_{{ $difficulty->slug() }}_content"
                 class="tab-pane fade show {{ $loop->first ? 'active' : '' }}"
                 role="tabpanel"
                 aria-labelledby="admin_speedrun_required_npcs_{{ $difficulty->slug() }}_tab">
                <table id="admin_speedrun_required_npcs_{{ $difficulty->slug() }}_table"
                       class="admin_speedrun_required_npcs_table tablesorter default_table table-striped">
                    <thead>
                    <tr>
                        <th width="10%">{{ __('view_admin.floor.edit.speedrun_required_npcs.table_header_id') }}</th>
                        <th width="70%">{{ __('view_admin.floor.edit.speedrun_required_npcs.table_header_npc') }}</th>
                        <th width="10%">{{ __('view_admin.floor.edit.speedrun_required_npcs.table_header_count') }}</th>
                        <th width="10%">{{ __('view_admin.floor.edit.speedrun_required_npcs.table_header_actions') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach ($npcsByDifficulty->get($difficulty->value) as $speedrunRequiredNpc)
                        <tr>
                            <td>{{ $speedrunRequiredNpc->id }}</td>
                            <td>{{ $speedrunRequiredNpc->getDisplayText() }}</td>
                            <td>{{ $speedrunRequiredNpc->count }}</td>
                            <td>
                                <form method="POST"
                                      action="{{
                                        route('admin.dungeonspeedrunrequirednpc.delete', [
                                            'dungeon' => $dungeon,
                                            'floor' => $floor,
                                            'dungeonspeedrunrequirednpc' => $speedrunRequiredNpc->id,
                                            'difficulty' => $difficulty->value,
                                        ])
                                        }}"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>&nbsp;{{ __('view_admin.floor.edit.speedrun_required_npcs.npc_delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endif
