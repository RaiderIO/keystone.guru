<?php
/** @var \Illuminate\Support\Collection|\App\Models\DungeonRoute[] $demoRoutes */
?>

@include('common.dungeon.grid', [
    'expansionService' => $expansionService,
    'dungeons' => \App\Models\Dungeon::whereIn('id', $demoRoutes->pluck(['dungeon_id']))->get(),
    'links' => $demoRoutes->map(function($dungeonRoute){
        return ['dungeon' => $dungeonRoute->dungeon->key, 'link' => route('dungeonroute.view', ['dungeonroute' => $dungeonRoute->public_key])];
    })
])