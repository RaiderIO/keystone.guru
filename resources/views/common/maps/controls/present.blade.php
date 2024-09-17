<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use Illuminate\Support\Collection;

/**
 * @var boolean           $isAdmin
 * @var Collection<Floor> $floors
 * @var DungeonRoute|null $dungeonroute
 */
?>
<nav class="route_sidebar left row no-gutters map_fade_out top presenter {{ $isMobile ? 'mobile' : '' }}">
    <div class="col pt-4 pl-4">
        <h2>
            +{{ $dungeonroute->challengeModeRun->level }}

            @if($dungeonroute->affixes->isNotEmpty())
                    <?php
                    /** @var AffixGroup $affixGroup */
                    $affixGroup = $dungeonroute->affixes->first()
                    ?>

                @foreach($affixGroup->affixes as $affix)
                    <img src="{{ url(sprintf('/images/affixes/%s.jpg', \Str::slug($affix->key, '_'))) }}"
                         alt="{{ __($affix->name) }}" style="border-radius: 4px;"/>
                @endforeach
            @endif
        </h2>
    </div>
</nav>

<nav class="route_sidebar left row no-gutters map_fade_out top presenter" style="top: 90px">
    <div class="col pl-3">
        <h2>
            <i class="fas fa-stopwatch"></i>
            {{ $dungeonroute->challengeModeRun->getFormattedElapsedTime() }}
        </h2>
    </div>
</nav>

{{--<nav class="route_sidebar left row no-gutters map_fade_out top presenter" style="top: 140px">--}}
{{--    <div class="col pt-4 pl-4">--}}
{{--        <div class="row mb-2">--}}
{{--            <div class="col">--}}
{{--                <h4>--}}
{{--                    Player 1--}}
{{--                </h4>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row mb-2">--}}
{{--            <div class="col">--}}
{{--                <h4>--}}
{{--                    Player 2--}}
{{--                </h4>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row mb-2">--}}
{{--            <div class="col">--}}
{{--                <h4>--}}
{{--                    Player 3--}}
{{--                </h4>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row mb-2">--}}
{{--            <div class="col">--}}
{{--                <h4>--}}
{{--                    Player 4--}}
{{--                </h4>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row mb-2">--}}
{{--            <div class="col">--}}
{{--                <h4>--}}
{{--                    Player 5--}}
{{--                </h4>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</nav>--}}

<nav class="route_sidebar route_manipulation_tools left row no-gutters map_fade_out top presenter" style="top: 370px">
    <div class="col">
        <div id="present_route_actions_container" class="mb-2">
            @auth
                @if($dungeonroute->mayUserEdit(Auth::user()))
                    @include('common.maps.controls.elements.dungeonroute.edit', ['dungeonroute' => $dungeonroute])
                @endif
                @if($dungeonroute->dungeon->active)
                    @include('common.maps.controls.elements.dungeonroute.clone', ['dungeonroute' => $dungeonroute])
                @endif
            @endauth

            @isset($dungeonroute)
                @include('common.maps.controls.elements.dungeonroute.info', ['dungeonroute' => $dungeonroute])
            @endisset
        </div>

        <div id="present_route_map_actions_container" class="mb-2">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])
        </div>

        <div id="present_route_misc_actions_container">
            @include('common.maps.controls.elements.labeltoggle')
        </div>
    </div>
</nav>
