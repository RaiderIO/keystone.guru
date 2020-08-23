<?php
// @TODO This should be handled differently - defined both here and in app.blade.php
/** @var \Illuminate\Support\Collection|\App\Models\DungeonRoute[] $demoRoutes */
$demoRoutes = \App\Models\DungeonRoute::where('demo', true)->where('published', true)->orderBy('dungeon_id')->get();
?>

@include('common.dungeon.grid', [
    'expansionService' => $expansionService,
    'links' => $demoRoutes->map(function($dungeonRoute){
        return ['dungeon' => $dungeonRoute->dungeon->key, 'link' => route('dungeonroute.view', ['dungeonroute' => $dungeonRoute->public_key])];
    })
])