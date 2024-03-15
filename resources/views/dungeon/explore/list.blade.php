@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('view_dungeon.explore.list.title')
])

@section('header-title', __('view_dungeon.explore.list.header'))

@section('content')
    @include('common.dungeon.gridtabs', [
        'id' => 'explore_dungeon',
        'tabsId' => 'explore_dungeon_select_tabs',
        'route' => 'dungeon.explore.view'
    ])
@endsection
