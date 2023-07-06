<?php
    /**
     * @var $showAllEnabled bool
     * @var $edit bool
     */
?>
<div id="edit_route_dungeon_speedrun_scroll_container" class="{{ $edit ? 'edit' : '' }}" data-simplebar>
    <div id="edit_route_dungeon_speedrun_required_npcs_container"></div>
    <div class="collapse {{ $showAllEnabled ? 'show' : '' }}"
         id="edit_route_dungeon_speedrun_required_npcs_collapse">
        <div id="edit_route_dungeon_speedrun_required_npcs_container_overflow">

        </div>
    </div>
    <div class="px-2">
        <button class="btn btn-primary w-100" type="button" data-toggle="collapse"
                data-target="#edit_route_dungeon_speedrun_required_npcs_collapse"
                aria-expanded="false" aria-controls="collapseExample">
            {{ __('views/common.maps.controls.pulls.toggle_all_required_enemies') }}

        </button>
    </div>
</div>
