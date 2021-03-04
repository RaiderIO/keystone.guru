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
    <div class="container discover">
        @include('dungeonroute.discover.dungeon.panel', ['title' => __('Popular'), 'dungeonroutes' => $dungeonroutes['popular']])
        @include('dungeonroute.discover.dungeon.panel', ['title' => __('This week'), 'dungeonroutes' => $dungeonroutes['thisweek']])
        @include('dungeonroute.discover.dungeon.panel', ['title' => __('Next week'), 'dungeonroutes' => $dungeonroutes['nextweek']])
        @include('dungeonroute.discover.dungeon.panel', ['title' => __('New'), 'dungeonroutes' => $dungeonroutes['new']])
    </div>
@endsection