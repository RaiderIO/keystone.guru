<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion $gameVersion
 * @var Dungeon     $model
 * @var array       $parameters
 */

$showStyle = 'regular';
?>
@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('view_misc.embed.title')])

@section('header-title', __('view_misc.embed.header'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-6">
            @if(!empty($parameters))
                <iframe src="{{ route('dungeon.explore.gameversion.embed', array_merge([
                        'gameVersion' => $gameVersion,
                        'dungeon' => $model
                    ], $parameters)) }}"
                        style="width: 800px; height: 600px; border: none;"></iframe>
            @elseif($showStyle === 'compact')
                <iframe src="{{ route('dungeon.explore.gameversion.embed', [
                        'gameVersion' => $gameVersion,
                        'dungeon' => $model,
                        'style' => 'compact',
                        'headerBackgroundColor' => '#0F0',
                        'mapBackgroundColor' => '#F00',
                        'showEnemyInfo' => 0,
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
