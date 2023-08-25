@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('views/dungeon.explore.list.title')
])

@section('header-title', __('views/dungeon.explore.list.header'))

@section('content')
    @include('common.dungeon.gridtabs', [
        'id' => 'explore_dungeon',
        'tabsId' => 'explore_dungeon_select_tabs',
        'route' => 'dungeon.explore.view'
    ])
@endsection
