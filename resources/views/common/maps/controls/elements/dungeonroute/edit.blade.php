<?php
/** @var \App\Models\DungeonRoute\Dungeonroute $dungeonroute */
?>
<div class="row no-gutters">
    <div class="col">
        <a href="{{ route('dungeonroute.edit', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}"
           class="btn btn-info">
            <i class="fas fa-edit"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.view.edit_this_route_title') }}
            </span>
        </a>
    </div>
</div>
