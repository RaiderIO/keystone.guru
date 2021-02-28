<?php
/** @var \Illuminate\Support\Collection|\App\Models\DungeonRoute[] $demoRoutes */
?>

@include('common.dungeon.grid', [
    'dungeons' => $dungeons,
    'links' => $demoRoutes->map(function($dungeonRoute){
        return ['dungeon' => $dungeonRoute->dungeon->key, 'link' => route('dungeonroute.view', ['dungeonroute' => $dungeonRoute->public_key])];
    })
])