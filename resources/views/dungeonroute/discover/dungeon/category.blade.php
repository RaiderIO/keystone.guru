<?php
/**
 * @var $dungeon \App\Models\Dungeon
 * @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection
 */
$title = isset($title) ? $title : sprintf('%s routes', $dungeon->name);
?>
@extends('layouts.sitepage', ['custom' => true, 'title' => $title])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ]
])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])
    <div class="container discover" style="z-index: 100;">
        <div class="mt-4">
            {{ Diglactic\Breadcrumbs\Breadcrumbs::render($breadcrumbs, $dungeon) }}
        </div>

        @if( $showAds && !$isMobile)
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['id' => 'site_top_header', 'type' => 'header', 'reportAdPosition' => 'top-right'])
            </div>
        @endif

        <div class="discover_panel">
            <h2 class="text-center">
                {{ $title }}
            </h2>

            @include('common.dungeonroute.cardlist', [
                'cols' => 2,
                'dungeonroutes' => $dungeonroutes
            ])
        </div>
    </div>
@endsection