<?php
use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute $dungeonroute
 **/
?>
<div class="row no-gutters">
    <div class="col">
        <a href="{{ route('dungeonroute.clone', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}"
           class="btn btn-info">
            <i class="fas fa-clone"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.view.clone_this_route_title') }}
            </span>
        </a>
    </div>
</div>
