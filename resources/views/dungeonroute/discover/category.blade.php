<?php
/**
 * @var $category string
 * @var $dungeon \App\Models\Dungeon
 * @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection
 */
$title = isset($title) ? $title : sprintf('%s routes', $dungeon->name);
?>
@extends('layouts.sitepage', ['rootClass' => 'discover col-xl-10 offset-xl-1', 'title' => $title])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('content')
    <div class="discover_panel">

        <h2 class="text-center">
            {{ $title }}
        </h2>

        <div id="category_route_list">
            @include('common.dungeonroute.cardlist', [
                'cols' => 2,
                'dungeonroutes' => $dungeonroutes,
                'showDungeonImage' => true,
            ])
        </div>

        @include('common.dungeonroute.search.loadmore', ['category' => $category, 'routeListContainerSelector' => '#category_route_list'])
    </div>
@endsection