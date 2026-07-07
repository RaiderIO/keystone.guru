<?php

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Collection<int, array{id: int, text: string}>> $dungeonStartsByDungeonId
 * @var DungeonRoute|null                                              $dungeonroute
 * @var string                                                         $dungeonSelectId
 **/

$id           ??= 'dungeon_start_map_icon_id';
$dungeonroute ??= null;
$selectedId   = $dungeonroute?->dungeon_start_map_icon_id;
?>
@include('common.general.inline', [
    'path'    => 'common/dungeonroute/create/dungeonstartselect',
    'options' => [
        'dungeonSelectId'         => sprintf('#%s', $dungeonSelectId),
        'dungeonStartSelectId'    => sprintf('#%s', $id),
        'dungeonStartContainerId' => sprintf('#%s_container', $id),
        'dungeonStartsByDungeonId' => $dungeonStartsByDungeonId,
        'selectedDungeonStartId'  => $selectedId !== null ? (int)$selectedId : null,
    ],
])

<div id="{{ $id }}_container" class="mb-3" style="display: none;">
    <label for="{{ $id }}">
        {{ __('view_common.forms.createroute.dungeon_start') }}
        <i class="fas fa-info-circle" data-bs-toggle="tooltip" title="{{
            __('view_common.forms.createroute.dungeon_start_title')
             }}"></i>
    </label>
    {{ html()->select('dungeon_start_map_icon_id', [], $selectedId)->id($id)->class('form-control selectpicker') }}
</div>
