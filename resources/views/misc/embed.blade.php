<?php

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Str;

/**
 * @var DungeonRoute         $model
 * @var array<string, mixed> $parameters
 */

$showStyle = 'regular';
?>
@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('view_misc.embed.title')])

@section('header-title', __('view_misc.embed.header'))

@section('scripts')
    @parent
    <script type="text/javascript">
        $(function () {
            let requestId = 0;

            window.addEventListener('message', function (event) {
                if (event.source !== $('#ksg_iframe')[0].contentWindow || event.data.function !== 'mdtString') {
                    return;
                }

                $('#mdt_string_result').val(JSON.stringify(event.data, null, 2));
            });

            $('#get_mdt_string').on('click', function () {
                $('#ksg_iframe')[0].contentWindow.postMessage({
                    function: 'getMdtString',
                    requestId: `request-${++requestId}`,
                }, '*');
            });
        });
    </script>
@endsection

@section('content')
    <div class="mb-3 row">
        <div class="col-auto">
            <button id="get_mdt_string" type="button" class="btn btn-primary">
                {{ __('view_misc.embed.get_mdt_string') }}
            </button>
        </div>
        <div class="col">
            <textarea id="mdt_string_result" class="form-control" rows="3" readonly></textarea>
        </div>
    </div>

    <div class="row justify-content-lg-center">
        <div class="col">
            @if(!empty($parameters))
                <iframe
                    id="ksg_iframe"
                    src="{{ route('dungeonroute.embed', array_merge([
                        'dungeon' => $model->dungeon,
                        'dungeonroute' => $model,
                        'title' => Str::slug($model->title)],
                        $parameters
                    )) }}"
                    style="width: 100%; height: 600px; border: none;"></iframe>
            @elseif($showStyle === 'compact')
                <iframe
                    id="ksg_iframe"
                    src="{{ route('dungeonroute.embed', [
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
                <iframe
                    id="ksg_iframe"
                    src="{{ route('dungeonroute.embed', [
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
                    style="width: 100%; height: 600px; border: none;"></iframe>
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
