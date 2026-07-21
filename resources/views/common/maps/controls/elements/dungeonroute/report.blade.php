<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute $dungeonroute
 **/
?>
<div class="row g-0">
    <div class="col">
        <a href="#" data-bs-toggle="modal" data-bs-target="#userreport_dungeonroute_modal"
           class="btn btn-info {{ isset($current_report) ? 'disabled' : '' }}">
            <i class="fas fa-flag"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                                {{ isset($current_report) ?
                            __('view_common.maps.controls.view.report_for_moderation_finished') :
                            __('view_common.maps.controls.view.report_for_moderation') }}
                            </span>
        </a>
    </div>
</div>
