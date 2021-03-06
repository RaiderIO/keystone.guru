@extends('layouts.sitepage', ['custom' => true, 'title' => sprintf('%s routes', $dungeon->name)])

@section('header-title')
    {{ sprintf('%s routes', $dungeon->name) }}
@endsection
<?php
/**
 * @var $dungeon \App\Models\Dungeon
 * @var $dungeonroutes array
 */
?>

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ]
])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])

    <div class="container discover">
        <div class="mt-4">
            {{ Diglactic\Breadcrumbs\Breadcrumbs::render('dungeonroutes.discoverdungeon', $dungeon) }}
        </div>

        @include('dungeonroute.discover.panel', [
            'title' => __('Popular'),
            'showMore' => true,
            'link' => route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon]),
            'dungeonroutes' => $dungeonroutes['popular']
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('This week'),
            'showMore' => true,
            'link' => route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon]),
            'dungeonroutes' => $dungeonroutes['thisweek'],
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('Next week'),
            'showMore' => true,
            'link' => route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon]),
            'dungeonroutes' => $dungeonroutes['nextweek']
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('New'),
            'showMore' => true,
            'link' => route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon]),
            'dungeonroutes' => $dungeonroutes['new']
        ])
    </div>
@endsection