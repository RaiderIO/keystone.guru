<?php
$enemyVisualType  = $_COOKIE['enemy_display_type'] ?? 'enemy_portrait';
$enemyVisualTypes = [
    'enemy_portrait'  => __('view_common.maps.controls.elements.enemyvisualtype.portrait'),
    'npc_class'       => __('view_common.maps.controls.elements.enemyvisualtype.npc_class'),
    'npc_type'        => __('view_common.maps.controls.elements.enemyvisualtype.npc_type'),
    'enemy_forces'    => __('view_common.maps.controls.elements.enemyvisualtype.enemy_forces'),
    'enemy_skippable' => __('view_common.maps.controls.elements.enemyvisualtype.enemy_skippable'),
];
?>
<div class="row no-gutters">
    <div class="col btn-group dropright">
        <button type="button" class="btn btn-accent dropdown-toggle" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-users"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.elements.enemyvisualtype.enemy_visual_type_title') }}
            </span>
        </button>
        <div id="map_enemy_visuals_dropdown" class="dropdown-menu">
            <a class="dropdown-item disabled">
                {{ __('view_common.maps.controls.elements.enemyvisualtype.enemy_visual_type') }}
            </a>
            @foreach($enemyVisualTypes as $value => $text)
                <a class="dropdown-item {{ $value === $enemyVisualType ? 'active' : '' }}"
                   data-value="{{ $value }}">{{ $text }}</a>
            @endforeach
        </div>
    </div>
</div>
