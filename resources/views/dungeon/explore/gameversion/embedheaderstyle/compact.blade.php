<?php

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion $gameVersion
 * @var Dungeon     $dungeon
 * @var Floor       $floor
 * @var array       $embedOptions
 * @var array       $parameters
 */

$routeParams    = array_merge(['gameVersion' => $gameVersion, 'dungeon' => $dungeon, 'floorIndex' => $floor->index], $parameters);
$editHeatmapUrl = route('dungeon.explore.gameversion.view.floor', $routeParams);
?>
<header class="header_embed_compact px-0 px-md-2"
        style="
        @if($embedOptions['headerBackgroundColor'] !== null)
            background-color: {{ $embedOptions['headerBackgroundColor'] }};
        @else
            background-image: url({{ $dungeon->getImageUrl() }}); background-size: cover;
        @endif
        ">
    <div class="row no-gutters py-2">
        <div class="col-auto text-right pr-1">
            <div class="row no-gutters align-items-center" style="height: 36px;">
                <div class="col">
                <a href="{{ route('home') }}" target="_blank">
                    <img src="{{ url('/images/logo/logo_and_text.png') }}" class="header_embed_compact_logo"
                         alt="{{ config('app.name') }}">
                </a>
                </div>
            </div>
        </div>

        @if($embedOptions['show']['title'])
            <div class="col d-none d-md-block">
                <div class="row no-gutters align-items-center" style="height: 36px;">
                    <div class="col">
                        <h4 class="mb-0">
                            <a class="text-white"
                               href="{{ $editHeatmapUrl }}"
                               target="_blank">
                                {{ __('view_dungeon.explore.gameversion.embed.title', ['dungeon' => __($dungeon->name)]) }}
                            </a>
                        </h4>
                    </div>
                </div>
            </div>
        @else
            <?php // Fills up any remaining space space ?>
            <div class="col px-0">

            </div>
        @endif

        <div class="col-auto px-0 px-md-1">
            <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
            @if($embedOptions['show']['floorSelection'])
                {!! Form::select('map_floor_selection_dropdown', [__('view_dungeon.explore.gameversion.embed.select_floor')], 1, ['id' => 'map_floor_selection_dropdown', 'class' => 'form-control selectpicker']) !!}
            @endif
        </div>
        <div class="col-auto px-1 d-none d-md-block">
            <a class="btn btn btn-primary float-right"
               href="{{ $editHeatmapUrl }}"
               target="_blank">
                <i class="fas fa-external-link-alt"></i> {{ __('view_dungeon.explore.gameversion.embed.view_heatmap_fullscreen') }}
            </a>
        </div>
    </div>
</header>
