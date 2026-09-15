<?php
use App\Models\Enemy;

$enemyDisplayType  = Enemy::sanitizeDisplayType($_COOKIE['enemy_display_type'] ?? null);
$enemyDisplayTypes = [
    Enemy::DISPLAY_TYPE_ENEMY_PORTRAIT  => __('view_common.maps.controls.elements.enemydisplaytype.portrait'),
    Enemy::DISPLAY_TYPE_NPC_CLASS       => __('view_common.maps.controls.elements.enemydisplaytype.npc_class'),
    Enemy::DISPLAY_TYPE_NPC_TYPE        => __('view_common.maps.controls.elements.enemydisplaytype.npc_type'),
    Enemy::DISPLAY_TYPE_ENEMY_FORCES    => __('view_common.maps.controls.elements.enemydisplaytype.enemy_forces'),
    Enemy::DISPLAY_TYPE_ENEMY_GROUP     => __('view_common.maps.controls.elements.enemydisplaytype.enemy_group'),
    Enemy::DISPLAY_TYPE_ENEMY_SKIPPABLE => __('view_common.maps.controls.elements.enemydisplaytype.enemy_skippable'),
];
?>
<div class="row g-0">
    <div class="col btn-group dropend">
        <button type="button" class="btn btn-accent dropdown-toggle" data-bs-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-users"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.elements.enemydisplaytype.enemy_display_type_title') }}
            </span>
        </button>
        <div id="map_enemy_visuals_dropdown" class="dropdown-menu">
            <a class="dropdown-item disabled">
                {{ __('view_common.maps.controls.elements.enemydisplaytype.enemy_display_type') }}
            </a>
            @foreach($enemyDisplayTypes as $value => $text)
                <a class="dropdown-item {{ $value === $enemyDisplayType ? 'active' : '' }}"
                   data-value="{{ $value }}">{{ $text }}</a>
            @endforeach
        </div>
    </div>
</div>
