@extends('layouts.sitepage', ['title' => __('Routes')])

@section('header-title')
    {{ __('Routes') }}
@endsection
<?php
/**
 * @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection
 */
?>
@section('content')
    <h2>
        {{ __('Discover by dungeon') }}
    </h2>
    @include('common.dungeon.grid', [
        'dungeons' => $dungeons,
        'links' => $dungeons->map(function(\App\Models\Dungeon $dungeon){
            return ['dungeon' => $dungeon->key, 'link' => route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon->slug])];
        })
    ])
    <h2>
        {{ __('Popular routes (most views of last 7 days?)') }}
    </h2>
@endsection