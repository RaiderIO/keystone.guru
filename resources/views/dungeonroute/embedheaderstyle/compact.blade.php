<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;

/**
 * @var DungeonRoute $dungeonRoute
 * @var Dungeon      $dungeon
 * @var Floor        $floor
 * @var array        $embedOptions
 */

$routeParams     = ['dungeon' => $dungeonRoute->dungeon, 'dungeonroute' => $dungeonRoute, 'title' => $dungeonRoute->getTitleSlug()];
$presentRouteUrl = route('dungeonroute.present', $routeParams);
$viewRouteUrl    = route('dungeonroute.view', $routeParams);
?>
<header class="header_embed_compact  px-0 px-md-2"
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
        @if($embedOptions['show']['enemyForces'])
            <div class="row no-gutters align-items-center" style="height: 36px;">
                <div class="col-auto px-1">
                        <?php
                        // This is normally in the pulls sidebar - but for embedding it's in the header - see pulls.blade.php
                        ?>
                    <div id="edit_route_enemy_forces_container"></div>
                </div>
            </div>
        @endif
        @if($embedOptions['show']['affixes'])
            <div class="row no-gutters align-items-center" style="height: 36px;">
                <div class="col-md-auto px-1 d-md-flex d-none">
                        <?php
                        $mostRelevantAffixGroup = $dungeonRoute->getMostRelevantAffixGroup();
                        ?>
                    @if($mostRelevantAffixGroup !== null)
                        @include('common.affixgroup.affixgroup', ['affixgroup' => $mostRelevantAffixGroup, 'showText' => false, 'class' => 'w-100', 'isFirst' => true])
                    @endif
                </div>
            </div>
        @endif
        <?php // Fills up any remaining space space ?>
        <div class="col px-0">

        </div>
        <div class="col-auto px-1">
            <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
            @if($embedOptions['show']['floorSelection'])
                {!! Form::select('map_floor_selection_dropdown', [__('view_dungeonroute.embed.select_floor')], 1, ['id' => 'map_floor_selection_dropdown', 'class' => 'form-control selectpicker']) !!}
            @endif
        </div>
        @if($embedOptions['show']['presenterButton'])
            <div class="col-auto px-1">
                <a class="btn btn btn-warning float-right h-100 text-white"
                   href="{{ $presentRouteUrl }}"
                   target="_blank">
                    <i class="fas fa-video"></i> {{ __('view_dungeonroute.embed.present_route') }}
                </a>
            </div>
        @endif
        <div class="col-auto px-1">
            <a class="btn btn btn-primary float-right h-100"
               href="{{ $viewRouteUrl }}"
               target="_blank">
                <i class="fas fa-external-link-alt"></i> {{ __('view_dungeonroute.embed.view_route') }}
            </a>
        </div>
        @if($dungeon->mdt_supported)
            <div class="col-auto pl-1">
                <div id="embed_copy_mdt_string" class="btn btn btn-primary float-right h-100">
                    <i class="fas fa-file-export"></i> {{ __('view_dungeonroute.embed.copy_mdt_string') }}
                </div>
                <div id="embed_copy_mdt_string_loader" class="btn btn btn-primary float-right h-100" disabled
                     style="display: none;">
                    <i class="fas fa-circle-notch fa-spin"></i> {{ __('view_dungeonroute.embed.copy_mdt_string') }}
                </div>
            </div>
        @endif
    </div>
</header>
