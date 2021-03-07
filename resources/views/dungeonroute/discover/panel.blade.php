<?php
/** @var $title string */
/** @var $cols int */
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */

$cols = isset($cols) ? $cols : 2;
$showMore = isset($showMore) ? $showMore : false;
?>
<div class="discover_panel">
    <div class="row mt-4">
        <div class="col-xl">
            <h2 class="text-center">
                @isset($link)
                    <a href="{{ $link }}">
                        {{ $title }}
                    </a>
                @else
                    {{ $title }}
                @endisset
            </h2>
            @isset($affixgroup)
                <div class="row">
                    <div class="offset-2">
                    </div>
                    <div class="col-8">
                        @include('common.affixgroup.affixgroup', ['affixGroup' => $affixgroup])
                    </div>
                </div>
            @endisset
            @include('common.dungeonroute.cardlist', ['cols' => $cols, 'dungeonroutes' => $dungeonroutes])
        </div>
    </div>
    @if($showMore)
        <div class="row mt-4">
            <div class="col-xl text-center">
                <a href="{{ $link }}">
                    {{ __('> Show more') }}
                </a>
            </div>
        </div>
    @endif
</div>