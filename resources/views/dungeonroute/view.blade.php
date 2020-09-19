@extends('layouts.app', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $model->title])
<?php
/** @var $model \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */

$affixes = $model->affixes->pluck('text', 'id');
$selectedAffixes = $model->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes = [-1 => 'Any'];
    $selectedAffixes = -1;
}
$dungeon = \App\Models\Dungeon::findOrFail($model->dungeon_id);
?>
@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $model->affixes])
    @include('common.handlebars.groupsetup')

@endsection
@section('content')
    <div class="wrapper">
        @include('common.maps.viewsidebar', [
            'dungeon' => $dungeon,
            'model' => $model,
            'floorSelection' => (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1,
            'floorId' => $floor->id
        ])

        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $model,
            'edit' => false,
            'floorId' => $floor->id
        ])

        @include('common.maps.killzonessidebar', [
            'edit' => false
        ])
    </div>
@endsection

