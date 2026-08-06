<?php

use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * @var DungeonRoute|null        $dungeonroute
 * @var Collection<int, Dungeon> $allSpeedrunDungeons
 * @var string                   $dungeonSelectId
 **/

$id                  ??= 'dungeon_difficulty_select';
$dungeonroute        ??= null;
$difficultySelect    = collect(DungeonDifficulty::cases())
    ->mapWithKeys(fn(DungeonDifficulty $difficulty) => [$difficulty->value => $difficulty->translatedName()]);
$difficultyByDungeon = $allSpeedrunDungeons->mapWithKeys(fn(Dungeon $dungeon) => [
    $dungeon->id => collect(DungeonDifficulty::cases())->mapWithKeys(fn(DungeonDifficulty $difficulty) => [
        $difficulty->value => in_array($difficulty->value, $dungeon->getEnabledSpeedrunDifficulties(), true),
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
     class="mb-3"
     style="display: {{ isset($dungeonroute) && $dungeonroute->dungeon->speedrun_enabled ? '' : 'none' }} ">
    <label for="{{ $id }}">
        {{ __('view_common.forms.createroute.dungeon_speedrun_required_npc_difficulty') }}
        <i class="fas fa-info-circle" data-bs-toggle="tooltip" title="{{
            __('view_common.forms.createroute.dungeon_speedrun_required_npc_difficulty_title')
             }}"></i>
    </label>
    {{ html()->select('dungeon_difficulty', [], $dungeonroute?->difficulty ?? DungeonDifficulty::TWENTY_FIVE_MAN->value)->id($id)->class('form-control selectpicker') }}
</div>
