<?php
use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute $dungeonroute
 **/
?>
<div class="row g-0">
    <div class="col">
        <form method="POST" class="m-0"
              action="{{ route('dungeonroute.clone', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}">
            @csrf
            <button type="submit" class="btn btn-info">
                <i class="fas fa-clone"></i>
                <span class="map_controls_element_label_toggle" style="display: none;">
                    {{ __('view_common.maps.controls.view.clone_this_route_title') }}
                </span>
            </button>
        </form>
    </div>
</div>
