<?php
/** @var \App\Models\Dungeonroute $dungeonroute */
ob_start();
?>
<div id="map_dungeon_route_info_popover_container">
    <!-- Timer -->
    <div class="row no-gutters">
        <div class="col">
            {{ __('views/common.maps.controls.elements.dungeonrouteinfo.timer') }}
        </div>
    </div>
    <div class="row no-gutters">
        <div class="col pl-2" data-toggle="tooltip" title="{{ sprintf(__('views/common.maps.controls.elements.dungeonrouteinfo.timer_title'),
                        gmdate('i:s', $dungeonroute->dungeon->getTimerUpgradePlusTwoSeconds()),
                        gmdate('i:s', $dungeonroute->dungeon->getTimerUpgradePlusThreeSeconds()))
                        }}">
            {{ gmdate('i:s', $dungeonroute->dungeon->timer_max_seconds) }}
        </div>
    </div>

    <!-- Group setup -->
    <div class="row no-gutters">
        <div class="col">
            {{ __('views/common.maps.controls.elements.dungeonrouteinfo.group_setup') }}
        </div>
    </div>
    <div class="row no-gutters">
        <div id="view_dungeonroute_group_setup" class="col pl-2">
        </div>
    </div>


    <div class="row no-gutters">
        <div class="col">
            {{ __('views/common.maps.controls.elements.dungeonrouteinfo.affixes') }}
        </div>
    </div>
    @foreach($dungeonroute->affixes as $affixgroup)
        <div class="row no-gutters">
            @include('common.affixgroup.affixgroup', ['affixgroup' => $affixgroup, 'showText' => false, 'class' => 'w-100'])
        </div>
    @endforeach
</div>
<?php $content = ob_get_clean(); ?>
<!-- Dungeonroute info -->
<div class="row no-gutters">
    <div class="col" data-toggle="tooltip" data-placement="right"
         title="{{ __('views/common.maps.controls.elements.dungeonrouteinfo.route_info_title') }}">
        <button id="map_dungeon_route_info_popover" class="btn btn-info w-100" data-toggle="popover"
                data-placement="right"
                data-content="{{ $content }}" data-html="true">
            <i class="fa fa-info-circle"></i>
            <span class="map_controls_element_label_toggle" style="display: none;">
                {{ __('views/common.maps.controls.elements.dungeonrouteinfo.route_info_title') }}
            </span>
        </button>
    </div>
</div>
