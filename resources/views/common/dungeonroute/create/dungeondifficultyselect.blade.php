<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * @var DungeonRoute|null        $dungeonroute
 * @var Collection<int, Dungeon> $allSpeedrunDungeons
 * @var string                   $dungeonSelectId
 **/

$id                  ??= 'dungeon_difficulty_select';
$dungeonroute        ??= null;
$difficultySelect    = collect(Dungeon::DIFFICULTY_ALL)
    ->mapWithKeys(fn(int $difficultyId) => [$difficultyId => Dungeon::getDifficultyName($difficultyId)]);
$difficultyByDungeon = $allSpeedrunDungeons->mapWithKeys(fn(Dungeon $dungeon) => [
    $dungeon->id => collect(Dungeon::DIFFICULTY_ALL)->mapWithKeys(fn(int $difficultyId) => [
        $difficultyId => in_array($difficultyId, $dungeon->getEnabledSpeedrunDifficulties(), true),
    ]),
]);
?>
@include('common.general.inline', [
    'path'    => 'common/dungeonroute/create/dungeondifficultyselect',
    'options' => [
        'dungeonSelectSelector'                   => sprintf('#%s', $dungeonSelectId),
        'dungeonDifficultySelectSelector'         => sprintf('#%s', $id),
        'dungeonDifficultySelectContainerSelector' => sprintf('#%s_container', $id),
        'speedrunDungeonIds'                      => $allSpeedrunDungeons->pluck('id'),
        'difficultyByDungeon'                     => $difficultyByDungeon,
    ],
])

<div id="{{ $id }}_container"
     class="form-group"
     style="display: {{ isset($dungeonroute) && $dungeonroute->dungeon->speedrun_enabled ? '' : 'none' }} ">
    <label for="{{ $id }}">
        {{ __('view_common.forms.createroute.dungeon_speedrun_required_npc_difficulty') }}
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            __('view_common.forms.createroute.dungeon_speedrun_required_npc_difficulty_title')
             }}"></i>
    </label>
    {{ html()->select('dungeon_difficulty', [], $dungeonroute?->difficulty ?? Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_25_MAN])->id($id)->class('form-control selectpicker') }}
</div>
