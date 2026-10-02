<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion          $gameVersion
 * @var Dungeon              $model
 * @var string               $floorIndex
 * @var array<string, mixed> $parameters
 */
?>
@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('view_misc.embed.title')])

@section('header-title', __('view_misc.embed.header'))

@section('content')
    <div class="row justify-content-lg-center">
        <div class="col">
            <iframe
                id="ksg_iframe"
                src="{{ route('dungeon.explore.gameversion.embed.floor', array_merge([
                    'gameVersion' => $gameVersion,
                    'dungeon' => $model,
                    'floorIndex' => $floorIndex,
                ], $parameters)) }}"
                style="width: 100%; height: 600px; border: none;"></iframe>
        </div>
    </div>
    {{--    <div class="row">--}}
    {{--        <div class="col">--}}
    {{--            <iframe src="{{ route('dungeonroute.embed', ['dungeonroute' => $model, 'pulls' => 1, 'pullsDefaultState' => 0, 'enemyinfo' => 1]) }}"--}}
    {{--                    style="width: 100%; height: 600px; border: none;"></iframe>--}}
    {{--        </div>--}}
    {{--    </div>--}}
@endsection
