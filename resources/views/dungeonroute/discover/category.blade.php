<?php
/**
 * @var $category string
 * @var $dungeon \App\Models\Dungeon
 * @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection
 */
$title = $title ?? sprintf('%s routes', $dungeon->name);
$affixgroup = $affixgroup ?? null;
?>
@extends('layouts.sitepage', ['rootClass' => 'discover col-xl-10 offset-xl-1', 'title' => $title])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('content')

    @include('dungeonroute.discover.panel', [
        'cols' => 2,
        'category' => $category,
        'title' => $title,
        'dungeonroutes' => $dungeonroutes,
        'affixgroup' => $affixgroup,
        'showDungeonImage' => true,
        'loadMore' => true,
    ])

@endsection