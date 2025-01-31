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
<header class="header_embed_compact"
        style="
        @if($embedOptions['headerBackgroundColor'] !== null)
            background-color: {{ $embedOptions['headerBackgroundColor'] }};
        @else
            background-image: url({{ $dungeon->getImageUrl() }}); background-size: cover;
        @endif
        ">
    <div class="row no-gutters py-2">
        <div class="col-auto text-right pr-1">
            <a href="{{ route('home') }}" target="_blank">
                <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name') }}"
                     height="36px;" width="164px;">
            </a>
        </div>

        @if($embedOptions['show']['title'])
            <div class="col header_embed_text_ellipsis">
                <h4 class="mb-0">
                    <a class="text-white"
                       href="{{ $editHeatmapUrl }}"
                       target="_blank">
                        {{ __('view_dungeon.explore.gameversion.embed.title', ['dungeon' => __($dungeon->name)]) }}
                    </a>
                </h4>
            </div>
        @endif

        <?php // Fills up any remaining space space ?>
        <div class="col px-0">

        </div>
        <div class="col-auto px-1">
            <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
            @if($embedOptions['show']['floorSelection'])
                {!! Form::select('map_floor_selection_dropdown', [__('view_dungeon.explore.gameversion.embed.select_floor')], 1, ['id' => 'map_floor_selection_dropdown', 'class' => 'form-control selectpicker']) !!}
            @endif
        </div>
        <div class="col-auto px-1">
            <a class="btn btn btn-primary float-right"
               href="{{ $editHeatmapUrl }}"
               target="_blank">
                <i class="fas fa-external-link-alt"></i> {{ __('view_dungeon.explore.gameversion.embed.view_heatmap_fullscreen') }}
            </a>
        </div>
    </div>
</header>
