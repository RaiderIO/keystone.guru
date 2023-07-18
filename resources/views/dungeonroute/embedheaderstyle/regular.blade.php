<?php
/** @var $dungeonRoute \App\Models\DungeonRoute */
/** @var $dungeon \App\Models\Dungeon */
/** @var $floor \App\Models\Floor */
/** @var $embedOptions array */

$viewRouteUrl = route('dungeonroute.view', ['dungeon' => $dungeonRoute->dungeon, 'dungeonroute' => $dungeonRoute, 'title' => $dungeonRoute->getTitleSlug()]);
?>
<header class="header_embed_regular"
        style="
        @if($embedOptions['headerBackgroundColor'] !== null)
            background-color: {{ $embedOptions['headerBackgroundColor'] }};
        @else
            background-image: url({{ $dungeon->getImageUrl() }}); background-size: cover;
        @endif">
    <div class="row no-gutters py-2">
        <div class="col-8 pt-2">
            @if($embedOptions['show']['title'])
                <div class="row no-gutters">
                    <div class="col header_embed_text_ellipsis">
                        <a class="text-white"
                           href="{{ $viewRouteUrl }}"
                           target="_blank">
                            <h4 class="mb-0">
                                {{ $dungeonRoute->title }}
                            </h4>
                        </a>
                    </div>
                </div>
            @endif
        </div>
        <?php // Spacers ?>
        <div class="col">
        </div>
        <div class="col-auto text-right">
            <a href="{{ route('home') }}" target="_blank">
                <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name') }}"
                     height="44px;" width="200px;">
            </a>
        </div>
    </div>
    <div class="row no-gutters">
        <div class="col-auto pr-1">
            <div class="embed-header-subtitle">
                <?php
                // This is normally in the pulls sidebar - but for embedding it's in the header - see pulls.blade.php
                ?>
                @if($embedOptions['show']['enemyForces'])
                    <div id="edit_route_enemy_forces_container" class="pt-1"></div>
                @endif
            </div>
        </div>
        <div class="col-md-auto px-1 d-md-flex d-none">
            @if($embedOptions['show']['affixes'])
                    <?php
                    $mostRelevantAffixGroup = $dungeonRoute->getMostRelevantAffixGroup();
                    ?>
                @include('common.affixgroup.affixgroup', ['affixgroup' => $mostRelevantAffixGroup, 'showText' => false, 'class' => 'w-100'])
            @endif
        </div>
        <div class="col">
        </div>
        <div class="col-auto px-1">
            <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
            @if($dungeon->floors()->count() > 1)
                {!! Form::select('map_floor_selection_dropdown', [__('views/dungeonroute.embed.select_floor')], 1, ['id' => 'map_floor_selection_dropdown', 'class' => 'form-control selectpicker']) !!}
            @endif
        </div>
        <div class="col-auto px-1">
            <a class="btn btn btn-primary float-right h-100"
               href="{{ $viewRouteUrl }}"
               target="_blank">
                <i class="fas fa-external-link-alt"></i> {{ __('views/dungeonroute.embed.view_route') }}
            </a>
        </div>
        <div class="col-auto pl-1">
            <div id="embed_copy_mdt_string" class="btn btn-primary float-right">
                <i class="fas fa-file-export"></i> {{ __('views/dungeonroute.embed.copy_mdt_string') }}
            </div>
            <div id="embed_copy_mdt_string_loader" class="btn btn-primary float-right" disabled
                 style="display: none;">
                <i class="fas fa-circle-notch fa-spin"></i> {{ __('views/dungeonroute.embed.copy_mdt_string') }}
            </div>
        </div>
    </div>
</header>
