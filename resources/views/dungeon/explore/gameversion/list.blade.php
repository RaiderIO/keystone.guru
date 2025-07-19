<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion $gameVersion
 */
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('view_dungeon.explore.gameversion.list.title'),
    'breadcrumbsParams' => [$gameVersion],
])

@section('header-title', __('view_dungeon.explore.gameversion.list.header'))

@section('content')

    <div class="row form-group">
        <div class="col">
            <p>
                {{ __('view_dungeon.explore.gameversion.list.description') }}
            </p>
        </div>
    </div>

    @include('common.dungeon.gridtabs', [
        'id' => 'explore_dungeon',
        'tabsId' => 'explore_dungeon_select_tabs',
        'route' => 'dungeon.explore.gameversion.view',
        'routeParams' => ['gameVersion' => $gameVersion],
        'subtextFn' => function(Dungeon $dungeon) {
            $result = '';

            if( $dungeon->heatmap_enabled ) {
                echo '<div class="row no-gutters">
                    <div class="col">
                    </div>
                    <div class="col-auto px-2">
                        <i class="fas fa-fire text-danger" data-toggle="tooltip" title="'
                        . __('view_dungeon.explore.gameversion.list.heatmap_available') .
                        '"></i>
                    </div>
                </div>';
            }

            return $result;
        },
    ])
@endsection
