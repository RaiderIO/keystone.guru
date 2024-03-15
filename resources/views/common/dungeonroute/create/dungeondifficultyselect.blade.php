<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * @var DungeonRoute|null   $dungeonroute
 * @var Collection<Dungeon> $allSpeedrunDungeons
 * @var string              $dungeonSelectId
 **/

$id                  ??= 'dungeon_difficulty_select';
$dungeonroute        ??= null;
$difficultySelect    = collect([
    Dungeon::DIFFICULTY_10_MAN => __('dungeons.difficulty.1'),
    Dungeon::DIFFICULTY_25_MAN => __('dungeons.difficulty.2'),
]);
$difficultyByDungeon = $allSpeedrunDungeons->mapWithKeys(function (Dungeon $dungeon) {
    return [
        $dungeon->id => [
            Dungeon::DIFFICULTY_10_MAN => $dungeon->speedrun_difficulty_10_man_enabled,
            Dungeon::DIFFICULTY_25_MAN => $dungeon->speedrun_difficulty_25_man_enabled,
        ],
    ];
});
?>
@section('scripts')
    @parent

    <script>
        var _speedrunDungeonIds = {!! $allSpeedrunDungeons->pluck(['id']) !!};
        var _difficultySelect = {!! $difficultySelect !!};
        var _difficultyByDungeon = {!! $difficultyByDungeon !!};

        $(function () {
            let $dungeonSelect = $('#{{ $dungeonSelectId }}');
            $dungeonSelect.bind('change', function () {
                let $dungeonDifficultySelect = $('#{{ $id }}');
                let $dungeonDifficultySelectContainer = $('#{{ $id }}_container');

                let selectedDungeonId = parseInt($dungeonSelect.val());
                if (_speedrunDungeonIds.includes(selectedDungeonId)) {
                    let enabledDifficultyForDungeon = _difficultyByDungeon[selectedDungeonId];
                    $dungeonDifficultySelect.find('option').remove();

                    for (let difficultyId in enabledDifficultyForDungeon) {

                        if (enabledDifficultyForDungeon[difficultyId]) {
                            $dungeonDifficultySelect.append(jQuery('<option>', {
                                value: difficultyId,
                                text: lang.get(`dungeons.difficulty.${difficultyId}`)
                            }))
                        }
                    }

                    refreshSelectPickers();
                    $dungeonDifficultySelectContainer.show();
                } else {
                    $dungeonDifficultySelectContainer.hide();
                }
            });
        })
    </script>
@endsection

<div id="{{ $id }}_container"
     class="form-group"
     style="display: {{ isset($dungeonroute) && $dungeonroute->dungeon->speedrun_enabled ? '' : 'none' }} ">
    <label for="{{ $id }}">
        {{ __('view_common.forms.createroute.dungeon_speedrun_required_npc_difficulty') }}
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            __('view_common.forms.createroute.dungeon_speedrun_required_npc_difficulty_title')
             }}"></i>
    </label>
    {!! Form::select(
         'dungeon_difficulty', [],
         $dungeonroute?->difficulty ?? Dungeon::DIFFICULTY_25_MAN,
         ['id' => $id, 'class' => 'form-control selectpicker'])
     !!}
</div>
