<?php
/** @var \App\Models\Dungeonroute $dungeonroute */
?>
<div class="row no-gutters">
    <div class="col">
        <a href="#" data-toggle="modal" data-target="#userreport_dungeonroute_modal"
           class="btn btn-info {{ isset($current_report) ? 'disabled' : '' }}">
            <i class="fas fa-flag"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                                {{ isset($current_report) ?
                            __('views/common.maps.controls.view.report_for_moderation_finished') :
                            __('views/common.maps.controls.view.report_for_moderation') }}
                            </span>
        </a>
    </div>
</div>