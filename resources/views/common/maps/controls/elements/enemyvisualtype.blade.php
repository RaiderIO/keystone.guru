<?php
$enemyVisualType  = $_COOKIE['enemy_display_type'] ?? 'enemy_portrait';
$enemyVisualTypes = [
    'enemy_portrait' => __('views/common.maps.controls.elements.enemyvisualtype.portrait'),
    'npc_class'      => __('views/common.maps.controls.elements.enemyvisualtype.npc_class'),
    'npc_type'       => __('views/common.maps.controls.elements.enemyvisualtype.npc_type'),
    'enemy_forces'   => __('views/common.maps.controls.elements.enemyvisualtype.enemy_forces'),
];
?>
<div class="row no-gutters">
    <div class="col btn-group dropright">
        <button type="button" class="btn btn-accent dropdown-toggle" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false"
                data-tooltip="tooltip" data-placement="right"
                title="{{ __('views/common.maps.controls.elements.enemyvisualtype.enemy_visual_type_title') }}">
            <i class="fa fa-users"></i>
        </button>
        <div id="map_enemy_visuals_dropdown" class="dropdown-menu">
            <a class="dropdown-item disabled">
                {{ __('views/common.maps.controls.elements.enemyvisualtype.enemy_visual_type') }}
            </a>
            @foreach($enemyVisualTypes as $value => $text)
                <a class="dropdown-item {{ $value === $enemyVisualType ? 'active' : '' }}"
                   data-value="{{ $value }}">{{ $text }}</a>
            @endforeach
        </div>
    </div>
</div>
