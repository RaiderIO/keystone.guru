<?php

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Str;

/**
 * @var DungeonRoute $model
 * @var array        $parameters
 */

$showStyle = 'regular';
?>
@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('view_misc.embed.title')])

@section('header-title', __('view_misc.embed.header'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-6">
            @if(!empty($parameters))
                <iframe src="{{ route('dungeonroute.embed', array_merge([
                        'dungeon' => $model->dungeon,
                        'dungeonroute' => $model,
                        'title' => Str::slug($model->title)],
                        $parameters
                    )) }}"
                        style="width: 800px; height: 600px; border: none;"></iframe>
            @elseif($showStyle === 'compact')
                <iframe src="{{ route('dungeonroute.embed', [
                        'dungeon' => $model->dungeon,
                        'dungeonroute' => $model,
                        'title' => Str::slug($model->title),
                        'style' => 'compact',
                        'pulls' => 1,
                        'pullsDefaultState' => 0,
                        'headerBackgroundColor' => '#0F0',
                        'mapBackgroundColor' => '#F00',
                        'showEnemyInfo' => 0,
                        'showPulls' => 1,
                        'showEnemyForces' => 0,
                        'showAffixes' => 0,
                    ]) }}"
                        style="width: 800px; height: 600px; border: none;"></iframe>
            @elseif($showStyle === 'regular')
                <iframe src="{{ route('dungeonroute.embed', [
                        'dungeon' => $model->dungeon,
                        'dungeonroute' => $model,
                        'title' => Str::slug($model->title),
                        'style' => 'regular',
                        'pulls' => 1,
                        'pullsDefaultState' => 0,
//                        'headerBackgroundColor' => '#0F0',
//                        'mapBackgroundColor' => '#F00',
                        'showEnemyInfo' => 0,
                        'showPulls' => 1,
                        'showEnemyForces' => 1,
                        'showAffixes' => 1,
                        'showTitle' => 1,
                    ]) }}"
                        style="width: 800px; height: 600px; border: none;"></iframe>
            @endif
        </div>
    </div>
    {{--    <div class="row">--}}
    {{--        <div class="col">--}}
    {{--            <iframe src="{{ route('dungeonroute.embed', ['dungeonroute' => $model, 'pulls' => 1, 'pullsDefaultState' => 0, 'enemyinfo' => 1]) }}"--}}
    {{--                    style="width: 100%; height: 600px; border: none;"></iframe>--}}
    {{--        </div>--}}
    {{--    </div>--}}
@endsection
