<?php

use App\Models\DungeonRoute\DungeonRoute;

/** @var DungeonRoute $dungeonroute */

ob_start();
?>
<div id="map_dungeon_route_info_popover_container">
    <!-- Timer -->
    <div class="row g-0">
        <div class="col">
            {{ __('view_common.maps.controls.elements.dungeonrouteinfo.timer') }}
        </div>
    </div>
    <div class="row g-0">
        <div class="col ps-2" data-bs-toggle="tooltip" title="{{ sprintf(__('view_common.maps.controls.elements.dungeonrouteinfo.timer_title'),
                        gmdate('i:s', $dungeonroute->mappingVersion->getTimerUpgradePlusTwoSeconds()),
                        gmdate('i:s', $dungeonroute->mappingVersion->getTimerUpgradePlusThreeSeconds()))
                        }}">
            {{ gmdate('i:s', $dungeonroute->mappingVersion->timer_max_seconds) }}
        </div>
    </div>

    <!-- Group setup -->
    <div class="row g-0">
        <div class="col">
            {{ __('view_common.maps.controls.elements.dungeonrouteinfo.group_setup') }}
        </div>
    </div>
    <div class="row g-0">
        <div id="view_dungeonroute_group_setup" class="col ps-2">
        </div>
    </div>


    <div class="row g-0">
        <div class="col">
            {{ __('view_common.maps.controls.elements.dungeonrouteinfo.affixes') }}
        </div>
    </div>
    <div id="view_dungeonroute_affixes" class="row g-0">

    </div>
</div>
<?php $content = ob_get_clean(); ?>
    <!-- Dungeonroute info -->
<div class="row g-0">
    <div class="col" data-bs-toggle="tooltip" data-bs-placement="right">
        <button id="map_dungeon_route_info_popover" class="btn btn-info w-100"
                data-bs-content="{{ $content }}" data-bs-html="true">
            <i class="fa fa-info-circle"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.elements.dungeonrouteinfo.route_info_title') }}
            </span>
        </button>
    </div>
</div>
