<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion $gameVersion
 */
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('view_dungeon.heatmap.gameversion.list.title'),
    'breadcrumbsParams' => [$gameVersion],
])

@section('header-title', __('view_dungeon.heatmap.gameversion.list.header'))

@section('content')

    <div class="row form-group">
        <div class="col">
            <p>
                {!! __('view_dungeon.heatmap.gameversion.list.description', [
                    'raiderIO' => '<a href="https://raider.io/" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-external-link-alt"></i> ' .
                         __('view_dungeon.heatmap.gameversion.list.raider_io') .
                     '</a>',
                ]) !!}
            </p>
        </div>
    </div>

    @include('common.dungeon.gridtabs', [
        'id' => 'heatmap_dungeon',
        'tabsId' => 'heatmap_dungeon_select_tabs',
        'route' => 'dungeon.heatmap.gameversion.view',
        'routeParams' => ['gameVersion' => $gameVersion],
        'subtextFn' => function(Dungeon $dungeon) {
            $result = '';

            if( $dungeon->heatmap_enabled ) {
                echo '<div class="row no-gutters">
                    <div class="col">
                    </div>
                    <div class="col-auto px-2">
                        <i class="fas fa-fire text-danger" data-toggle="tooltip" title="'
                        . __('view_dungeon.heatmap.gameversion.list.heatmap_available') .
                        '"></i>
                    </div>
                </div>';
            }

            return $result;
        },
        'filterFn' => function(Dungeon $dungeon) {
            return $dungeon->heatmap_enabled && $dungeon->active;
        },
    ])
@endsection
