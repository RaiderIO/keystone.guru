<?php
/** @var \App\Models\DungeonRoute $model */
?>
@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('views/misc.embed.title')])

@section('header-title', __('views/misc.embed.header'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-6">
            <iframe src="{{ route('dungeonroute.embed', [
                        'dungeon' => $model->dungeon,
                        'dungeonroute' => $model,
                        'title' => $model->title,
                        'pulls' => 1,
                        'pullsDefaultState' => 0,
                        'enemyinfo' => 1
                    ]) }}"
                    style="width: 800px; height: 600px; border: none;"></iframe>
        </div>
    </div>
    {{--    <div class="row">--}}
    {{--        <div class="col">--}}
    {{--            <iframe src="{{ route('dungeonroute.embed', ['dungeonroute' => $model, 'pulls' => 1, 'pullsDefaultState' => 0, 'enemyinfo' => 1]) }}"--}}
    {{--                    style="width: 100%; height: 600px; border: none;"></iframe>--}}
    {{--        </div>--}}
    {{--    </div>--}}
@endsection
