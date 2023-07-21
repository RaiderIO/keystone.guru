<?php
/** @var boolean $isAdmin */
/** @var \Illuminate\Support\Collection $floors */
/** @var \App\Models\DungeonRoute|null $dungeonroute */

$dungeonroute->chall
?>
<nav class="route_sidebar left row no-gutters map_fade_out top presenter">
    <div class="col">
        <h1>
            Key level and affixes
        </h1>
    </div>
</nav>

<nav class="route_sidebar left row no-gutters map_fade_out top presenter" style="top: 65px">
    <div class="col">
        <h1>
            Timer info
        </h1>
    </div>
</nav>

<nav class="route_sidebar left row no-gutters map_fade_out top presenter" style="top: 130px">
    <div class="col">
        <div class="row mb-2">
            <div class="col">
                <h4>
                    Player 1
                </h4>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col">
                <h4>
                    Player 2
                </h4>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col">
                <h4>
                    Player 3
                </h4>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col">
                <h4>
                    Player 4
                </h4>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col">
                <h4>
                    Player 5
                </h4>
            </div>
        </div>
    </div>
</nav>

<nav class="route_sidebar route_manipulation_tools left row no-gutters map_fade_out top presenter" style="top: 350px">
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