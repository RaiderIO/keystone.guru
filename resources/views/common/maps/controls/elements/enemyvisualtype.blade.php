<?php
$enemyVisualType = $_COOKIE['enemy_display_type'] ?? 'enemy_portrait';
$enemyVisualTypes = [
    'enemy_portrait' => __('Portrait'),
    'npc_class' => __('Class'),
    'npc_type' => __('Type'),
    'enemy_forces' => __('Enemy forces'),
];
?>
<div class="row no-gutters">
    <div class="col btn-group dropright" data-toggle="tooltip" data-placement="right"
         title="{{ __('Enemy visual type') }}">
        <button type="button" class="btn btn-accent dropdown-toggle" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-users"></i>
        </button>
        <div id="map_enemy_visuals_dropdown" class="dropdown-menu">
            <a class="dropdown-item disabled">
                {{ __('Enemy visual type') }}
            </a>
            @foreach($enemyVisualTypes as $value => $text)
                <a class="dropdown-item {{ $value === $enemyVisualType ? 'active' : '' }}"
                   data-value="{{ $value }}">{{ $text }}</a>
            @endforeach
        </div>
    </div>
</div>