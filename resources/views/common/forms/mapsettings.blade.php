<?php

use App\Models\User;

$mapFacadeStyleChecked            = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;
$mapZoomSpeed                     = $_COOKIE['map_zoom_speed'] ?? '50';
$mapNumberStyleChecked            = ($_COOKIE['map_number_style'] ?? 'percentage') === 'percentage';
$mapHeatmapShowTooltips           = $_COOKIE['map_heatmap_show_tooltips'] ?? 1;
$mapUnkilledEnemyOpacity          = $_COOKIE['map_unkilled_enemy_opacity'] ?? '50';
$mapUnkilledImportantEnemyOpacity = $_COOKIE['map_unkilled_important_enemy_opacity'] ?? '80';
$mapEnemyAggressivenessBorder     = $_COOKIE['map_enemy_aggressiveness_border'] ?? 0;
$mapEnemyDangerousBorder          = $_COOKIE['map_enemy_dangerous_border'] ?? 0;
?>
<div class="draw_settings_tools container">

    <h4>{{ __('view_common.forms.mapsettings.general') }}</h4>

    <!-- Map facade style -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <label for="map_settings_map_facade_style">
                    {{ __('view_common.forms.mapsettings.map_facade_style') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{ __('view_common.forms.mapsettings.map_facade_style_title') }}"></i>
                </label>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <input id="map_settings_map_facade_style" type="checkbox"
                       {{ $mapFacadeStyleChecked ? 'checked' : '' }}
                       data-toggle="toggle" data-width="200px" data-height="20px"
                       data-onstyle="primary" data-offstyle="primary"
                       data-on="{{ __('view_common.forms.mapsettings.map_facade_style_facade_option') }}"
                       data-off="{{ __('view_common.forms.mapsettings.map_facade_style_split_floors_option') }}">
            </div>
        </div>
        <div class="row">
            <div class="col">
                {{ __('view_common.forms.mapsettings.map_facade_style_change_requires_page_refresh') }}
            </div>
        </div>
    </div>

    <!-- Map zoom speed -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <label for="map_settings_zoom_speed">
                    {{ __('view_common.forms.mapsettings.zoom_speed') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{ __('view_common.forms.mapsettings.zoom_speed_title') }}">

                    </i>
                </label>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <input id="map_settings_zoom_speed" class="form-control-range" type="range" min="0"
                       max="100" value="{{ $mapZoomSpeed }}">
            </div>
            <div class="col-auto value">
                {{ $mapZoomSpeed }}
            </div>
        </div>
    </div>

    <h4>{{ __('view_common.forms.mapsettings.heatmap') }}</h4>

    <!-- Heatmap tooltips -->
    <div class="form-group">
        <div class="row no-gutters">
            <div class="col pr-2">
                <label for="map_settings_heatmap_show_tooltips">
                    {{ __('view_common.forms.mapsettings.show_heatmap_tooltips') }}
                    <i class="fas fa-info-circle"
                       data-toggle="tooltip"
                       title="{{ __('view_common.forms.mapsettings.show_heatmap_tooltips_title') }}"></i>
                </label>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col pr-2">
                {{ html()->checkbox('map_settings_heatmap_show_tooltips', $mapHeatmapShowTooltips, 1)->id('map_settings_heatmap_show_tooltips')->class('form-control left_checkbox') }}
            </div>
        </div>
    </div>

    <h4>{{ __('view_common.forms.mapsettings.enemies') }}</h4>

    <!-- Enemy number style -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <label for="killzones_pulls_settings_map_number_style">
                    {{ __('view_common.forms.mapsettings.enemy_number_style') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{ __('view_common.forms.mapsettings.enemy_number_style_title') }}"></i>
                </label>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <input id="killzones_pulls_settings_map_number_style" type="checkbox"
                       {{ $mapNumberStyleChecked ? 'checked' : '' }}
                       data-toggle="toggle" data-width="200px" data-height="20px"
                       data-onstyle="primary" data-offstyle="primary"
                       data-on="{{ __('view_common.forms.mapsettings.percentage') }}"
                       data-off="{{ __('view_common.forms.mapsettings.enemy_forces') }}">
            </div>
        </div>
    </div>

    <!-- Unkilled enemy opacity -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <label for="map_settings_unkilled_enemy_opacity">
                    {{ __('view_common.forms.mapsettings.unkilled_enemy_opacity') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{ __('view_common.forms.mapsettings.unkilled_enemy_opacity_title') }}">

                    </i>
                </label>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <input id="map_settings_unkilled_enemy_opacity" class="form-control-range" type="range" min="0"
                       max="100" value="{{ $mapUnkilledEnemyOpacity }}">
            </div>
            <div class="col-auto value">
                {{ $mapUnkilledEnemyOpacity }}
            </div>
        </div>
    </div>

    <!-- Unkilled important enemy opacity -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <label for="map_settings_unkilled_important_enemy_opacity">
                    {{ __('view_common.forms.mapsettings.unkilled_important_enemy_opacity') }} <i
                        class="fas fa-info-circle" data-toggle="tooltip"
                        title="{{ __('view_common.forms.mapsettings.unkilled_important_enemy_opacity_title') }}"></i>
                </label>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <input id="map_settings_unkilled_important_enemy_opacity" class="form-control-range" type="range"
                       min="0" max="100" value="{{ $mapUnkilledImportantEnemyOpacity }}">
            </div>
            <div class="col-auto value">
                {{ $mapUnkilledImportantEnemyOpacity }}
            </div>
        </div>
    </div>

    <!-- Aggressiveness border -->
    <div class="form-group">
        <div class="row no-gutters">
            <div class="col pr-2">
                <label for="map_settings_enemy_aggressiveness_border">
                    {{ __('view_common.forms.mapsettings.show_aggressiveness_border') }}
                    <i class="fas fa-info-circle"
                       data-toggle="tooltip"
                       title="{{ __('view_common.forms.mapsettings.show_aggressiveness_border_title') }}"></i>
                </label>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col pr-2">
                {{ html()->checkbox('map_settings_enemy_aggressiveness_border', $mapEnemyAggressivenessBorder, 1)->id('map_settings_enemy_aggressiveness_border')->class('form-control left_checkbox') }}
            </div>
        </div>
    </div>

    <!-- Dangerous enemies -->
    <div class="form-group">
        <div class="row no-gutters">
            <div class="col pr-2">
                <label for="map_settings_enemy_dangerous_border">
                    {{ __('view_common.forms.mapsettings.highlight_dangerous_enemies') }}
                    <i class="fas fa-info-circle"
                       data-toggle="tooltip"
                       title="{{ __('view_common.forms.mapsettings.highlight_dangerous_enemies_title') }}">

                    </i>
                </label>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col pr-2">
                {{ html()->checkbox('map_settings_enemy_dangerous_border', $mapEnemyDangerousBorder, 1)->id('map_settings_enemy_dangerous_border')->class('form-control left_checkbox') }}
            </div>
        </div>
    </div>

    @if($edit)
        <h4 class="mt-4">{{ __('view_common.forms.mapsettings.drawing') }}</h4>
        <!-- Default line weight -->
        <div class="form-group">
            <div class="row">
                <div class="col">
                    <label for="edit_route_freedraw_options_weight">
                        {{ __('view_common.forms.mapsettings.default_line_weight') }}
                        <i class="fas fa-info-circle"
                           data-toggle="tooltip"
                           title="{{ __('view_common.forms.mapsettings.default_line_weight_title') }}">
                        </i>
                    </label>
                </div>
            </div>
            <div class="row">
                <div class="col line_weight_selection">
                        <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                    {{ html()->select('edit_route_freedraw_options_weight', [1, 2, 3, 4, 5], $_COOKIE['polyline_default_weight'] ?? 0)->id('edit_route_freedraw_options_weight')->class('form-control selectpicker') }}
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="edit_route_freedraw_options_weight">
                        {{ __('view_common.forms.mapsettings.default_line_color') }}
                        <i class="fas fa-info-circle"
                           data-toggle="tooltip"
                           title="{{ __('view_common.forms.mapsettings.default_line_color_title') }}">
                        </i>
                    </label>
                </div>
            </div>
            <div class="row">
                <div class="col default_color_selection">
                    <div id="edit_route_freedraw_options_color" class="w-100">

                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
