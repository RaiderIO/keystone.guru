<?php

use App\Models\DungeonRoute\Dungeonroute;

/** @var Dungeonroute $dungeonroute */

ob_start();
?>
<div id="map_dungeon_route_info_popover_container">
    <!-- Timer -->
    <div class="row no-gutters">
        <div class="col">
            {{ __('view_common.maps.controls.elements.dungeonrouteinfo.timer') }}
        </div>
    </div>
    <div class="row no-gutters">
        <div class="col pl-2" data-toggle="tooltip" title="{{ sprintf(__('view_common.maps.controls.elements.dungeonrouteinfo.timer_title'),
                        gmdate('i:s', $dungeonroute->mappingVersion->getTimerUpgradePlusTwoSeconds()),
                        gmdate('i:s', $dungeonroute->mappingVersion->getTimerUpgradePlusThreeSeconds()))
                        }}">
            {{ gmdate('i:s', $dungeonroute->mappingVersion->timer_max_seconds) }}
        </div>
    </div>

    <!-- Group setup -->
    <div class="row no-gutters">
        <div class="col">
            {{ __('view_common.maps.controls.elements.dungeonrouteinfo.group_setup') }}
        </div>
    </div>
    <div class="row no-gutters">
        <div id="view_dungeonroute_group_setup" class="col pl-2">
        </div>
    </div>


    <div class="row no-gutters">
        <div class="col">
            {{ __('view_common.maps.controls.elements.dungeonrouteinfo.affixes') }}
        </div>
    </div>
    @foreach($dungeonroute->affixes as $affixGroup)
        <?php /** @var object{first: bool} $loop */ ?>
        <div class="row no-gutters">
            @include('common.affixgroup.affixgroup', ['affixgroup' => $affixGroup, 'showText' => false, 'isFirst' => $loop->first, 'class' => 'w-100', 'cols' => 1])
        </div>
    @endforeach
</div>
<?php $content = ob_get_clean(); ?>
    <!-- Dungeonroute info -->
<div class="row no-gutters">
    <div class="col" data-toggle="tooltip" data-placement="right">
        <button id="map_dungeon_route_info_popover" class="btn btn-info w-100"
                data-content="{{ $content }}" data-html="true">
            <i class="fa fa-info-circle"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('view_common.maps.controls.elements.dungeonrouteinfo.route_info_title') }}
            </span>
        </button>
    </div>
</div>
