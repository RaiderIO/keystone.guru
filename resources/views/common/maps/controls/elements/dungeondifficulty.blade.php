<?php

use App\Models\DungeonDifficulty;

/**
 * @var list<DungeonDifficulty> $dungeonDifficulties
 */
?>
<div class="row g-0">
    <div class="col btn-group dropend">
        <button type="button" class="btn btn-accent dropdown-toggle" data-bs-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false"
                data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}'>
            <i class="fa fa-user-group"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.elements.dungeondifficulty.dungeon_difficulty') }}
            </span>
        </button>
        <div id="map_dungeon_difficulty_dropdown" class="dropdown-menu">
            <a class="dropdown-item disabled">
                {{ __('view_common.maps.controls.elements.dungeondifficulty.dungeon_difficulty') }}
            </a>
            @foreach($dungeonDifficulties as $index => $dungeonDifficulty)
                <a class="dropdown-item {{ $index === 0 ? 'active' : '' }}"
                   data-value="{{ $dungeonDifficulty->value }}">{{ $dungeonDifficulty->translatedName() }}</a>
            @endforeach
        </div>
    </div>
</div>
