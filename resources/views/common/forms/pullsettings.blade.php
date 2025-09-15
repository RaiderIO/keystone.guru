<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute|null $dungeonroute
 **/

$killZonesNumberStyleChecked       = ($_COOKIE['kill_zones_number_style'] ?? 'percentage') === 'percentage';
$pullsSidebarFloorSwitchVisibility = ($_COOKIE['pulls_sidebar_floor_switch_visibility'] ?? '1') === '1';
?>
<div class="pull_settings_tools container">
    <div class="form-group">
        <div class="row">
            <div class="col">
                {{ __('view_common.forms.pullsettings.pull_number_style') }}
                <i class="fas fa-info-circle"
                   data-toggle="tooltip"
                   title="{{ __('view_common.forms.pullsettings.pull_number_style_title') }}">

                </i>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <input id="killzones_pulls_settings_number_style" type="checkbox"
                       {{ $killZonesNumberStyleChecked ? 'checked' : '' }}
                       data-toggle="toggle" data-width="200px" data-height="20px"
                       data-onstyle="primary" data-offstyle="primary"
                       data-on="{{ __('view_common.forms.pullsettings.pull_number_style_percentage') }}"
                       data-off="{{ __('view_common.forms.pullsettings.pull_number_style_enemy_forces') }}">
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col">
                {{ __('view_common.forms.pullsettings.show_floor_breakdown') }}
                <i class="fas fa-info-circle"
                   data-toggle="tooltip"
                   title="{{ __('view_common.forms.pullsettings.show_floor_breakdown_title') }}"></i>
            </div>
        </div>
        <div class="row">
            <div class="col">
                {{ html()->checkbox('pulls_sidebar_floor_switch_visibility', $pullsSidebarFloorSwitchVisibility, 1)->id('pulls_sidebar_floor_switch_visibility')->class('form-control left_checkbox') }}
            </div>
        </div>
    </div>

    @if(isset($dungeonroute) && $edit)
        <div class="form-group">
            <div class="row view_dungeonroute_details_row mt-2">
                <div class="col">
                    {{ __('view_common.forms.pullsettings.pull_color_gradient') }}
                    <i class="fas fa-info-circle"
                       data-toggle="tooltip"
                       title="{{ __('view_common.forms.pullsettings.pull_color_gradient_title') }}">

                    </i>
                </div>
            </div>
            <div class="row view_dungeonroute_details_row mt-3">
                <div id="edit_route_freedraw_options_gradient" class="col-10">

                </div>
                <div class="col-2">
                    <button id="edit_route_freedraw_options_gradient_apply_to_pulls"
                            class="btn btn-success"
                            data-toggle="tooltip"
                            title="{{ __('view_common.forms.pullsettings.apply_now_title') }}">
                        {{ __('view_common.forms.pullsettings.apply_now_title') }}
                    </button>
                    <button id="edit_route_freedraw_options_gradient_apply_to_pulls_saving"
                            class="btn btn-success disabled"
                            style="display: none">
                        <i class='fas fa-circle-notch fa-spin'></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row no-gutters view_dungeonroute_details_row">
                <div class="col pr-2">
                    <label for="pull_gradient_apply_always">
                        {{ __('view_common.forms.pullsettings.always_apply_on_pull_change') }}
                        <i class="fas fa-info-circle"
                           data-toggle="tooltip"
                           title="{{ __('view_common.forms.pullsettings.always_apply_on_pull_change_title') }}">

                        </i>
                    </label>
                </div>
            </div>
            <div class="row no-gutters view_dungeonroute_details_row">
                <div class="col pr-2">
                    {{ html()->checkbox('pull_gradient_apply_always', $dungeonroute->pull_gradient_apply_always, 1)->id('pull_gradient_apply_always')->class('form-control left_checkbox') }}
                </div>
            </div>
        </div>
    @endisset
</div>
