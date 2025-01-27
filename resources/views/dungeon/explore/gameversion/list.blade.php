<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;

/**
 * @var boolean     $showRunCountPerDungeon
 * @var GameVersion $gameVersion
 */
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('view_dungeon.explore.list.title'),
    'breadcrumbsParams' => [$gameVersion],
])

@section('header-title', __('view_dungeon.explore.list.header'))

@section('content')
    @include('common.dungeon.gridtabs', [
        'id' => 'explore_dungeon',
        'tabsId' => 'explore_dungeon_select_tabs',
        'route' => 'dungeon.explore.gameversion.view',
        'routeParams' => ['gameVersion' => $gameVersion],
        'subtextFn' => function(Dungeon $dungeon) use ($showRunCountPerDungeon) {
            $result = '';

            if( $showRunCountPerDungeon && $dungeon->heatmap_enabled ) {
                echo '<div class="row no-gutters">
                    <div class="col">
                    </div>
                    <div class="col-auto px-2">
                        <i class="fas fa-fire text-danger" data-toggle="tooltip" title="'
                        . __('view_dungeon.explore.list.heatmap_available') .
                        '"></i>
                    </div>
                </div>';
            }

            return $result;
        },
    ])
@endsection
