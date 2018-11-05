@extends('layouts.app', ['custom' => true, 'footer' => false, 'header' => false])
<?php
/** @var $model \App\Models\DungeonRoute */
$affixes = $model->affixes->pluck('text', 'id');
$selectedAffixes = $model->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes = [-1 => 'Any'];
    $selectedAffixes = -1;
}
$dungeon = \App\Models\Dungeon::findOrFail($model->dungeon_id);
$floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
?>
@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $model->affixes])
    @include('common.handlebars.groupsetup')

@endsection
@section('content')
    <div class="wrapper">
        @include('common.maps.viewsidebar', ['model' => $model, 'floorSelection' => $floorSelection])

        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $model,
            'edit' => false
        ])
    </div>
@endsection

