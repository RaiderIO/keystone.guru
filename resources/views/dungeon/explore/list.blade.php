<?php

use App\Models\Dungeon;
use Illuminate\Support\Collection;

/**
 * @var Collection<int> $runCountPerDungeon
 */
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('view_dungeon.explore.list.title'),
])

@section('header-title', __('view_dungeon.explore.list.header'))

@section('content')
    @include('common.dungeon.gridtabs', [
        'id' => 'explore_dungeon',
        'tabsId' => 'explore_dungeon_select_tabs',
        'route' => 'dungeon.explore.view',
        'subtextFn' => function(Dungeon $dungeon) use ($runCountPerDungeon) {
            $result = '';

            if( $runCountPerDungeon->has($dungeon->id) ) {
                $runCount = $runCountPerDungeon->get($dungeon->id);
                echo '<div class="row no-gutters">
                    <div class="col">
                    </div>
                    <div class="col-auto px-2">
                        <i class="fas fa-fire text-danger" data-toggle="tooltip" title="'
                        . __('view_dungeon.explore.list.heatmap_available', ['runCount' => $runCount]) .
                        '"></i>
                    </div>
                </div>';
            }

            return $result;
        },
    ])
@endsection
