<?php

/** @var \App\Models\DungeonRoute|null $dungeonroute */
/** @var \App\Models\Dungeon[]|\Illuminate\Support\Collection $allSpeedrunDungeons */
/** @var string $dungeonSelectId */

$id           = $id ?? 'dungeon_speedrun_required_npc_mode';
$dungeonroute = $dungeonroute ?? null;
?>
@section('scripts')
    @parent

    <script>
        $(function () {
            let $speedrunRequiredNpcModeSelect = $('#{{ $dungeonSelectId }}');
            $speedrunRequiredNpcModeSelect.bind('change', function () {
                let $container = $('#{{ $id }}_container');

                if ({!! $allSpeedrunDungeons->pluck(['id']) !!}.includes(parseInt($speedrunRequiredNpcModeSelect.val()))) {
                    $container.show();
                } else {
                    $container.hide();
                }
            });
        })
    </script>
@endsection

<div id="{{ $id }}_container"
     class="form-group"
     style="display: {{ isset($dungeonroute) && $dungeonroute->dungeon->speedrun_enabled ? '' : 'none' }} ">
    <label for="dungeon_speedrun_required_npc_mode">
        {{ __('views/common.forms.createroute.dungeon_speedrun_required_npc_difficulty') }}
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            __('views/common.forms.createroute.dungeon_speedrun_required_npc_difficulty_title')
             }}"></i>
    </label>
    {!! Form::select(
         'dungeon_difficulty', [
             \App\Models\Dungeon::DIFFICULTY_10_MAN => __('dungeons.difficulty.1'),
             \App\Models\Dungeon::DIFFICULTY_25_MAN => __('dungeons.difficulty.2'),
         ],
         optional($dungeonroute)->dungeon_speedrun_required_npc_mode ?? \App\Models\Dungeon::DIFFICULTY_25_MAN,
         ['id' => $id, 'class' => 'form-control selectpicker'])
     !!}
</div>
