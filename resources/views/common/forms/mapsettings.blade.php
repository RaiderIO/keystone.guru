<?php
$mapNumberStyleChecked = ($_COOKIE['map_number_style'] ?? 'percentage') === 'percentage';
$mapUnkilledEnemyOpacity = $_COOKIE['map_unkilled_enemy_opacity'] ?? '50';
$mapUnkilledImportantEnemyOpacity = $_COOKIE['map_unkilled_important_enemy_opacity'] ?? '80';
$mapEnemyAggressivenessBorder = $_COOKIE['map_enemy_aggressiveness_border'] ?? 0;
$mapEnemyDangerousBorder = $_COOKIE['map_enemy_dangerous_border'] ?? 0;
?>
<div class="draw_settings_tools container">
    <h4>{{ __('Enemies') }}</h4>

    <!-- Enemy number style -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <label for="killzones_pulls_settings_map_number_style">
                    {{ __('Enemy number style') }} <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                    __('This controls what the numbers mean when you mouse over enemies or when you select the \'Enemy forces\' display type.')
                     }}"></i>
                </label>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <input id="killzones_pulls_settings_map_number_style" type="checkbox"
                       {{ $mapNumberStyleChecked ? 'checked' : '' }}
                       data-toggle="toggle" data-width="150px" data-height="20px"
                       data-onstyle="primary" data-offstyle="primary"
                       data-on="{{ __('Percentage') }}" data-off="{{ __('Enemy forces') }}">
            </div>
        </div>
    </div>

    <!-- Unkilled enemy opacity -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <label for="map_settings_unkilled_enemy_opacity">
                    {{ __('Unkilled enemy opacity') }} <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                    __('This option allows you to fade out enemies that are not part of any of your pulls. This can reduce the clutter of enemies you want to ignore.')
                     }}"></i>
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
                    {{ __('Unkilled important enemy opacity') }} <i class="fas fa-info-circle" data-toggle="tooltip"
                                                                    title="{{
                    __('Important enemies are those that are either Prideful, marked as Inspiring or are marked as unskippable.
                    These can be rendered at a different opacity than other enemies to highlight their importance should you reduce the opacity of all other enemies.')
                     }}"></i>
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
                    {{ __('Show aggressiveness border') }} <i class="fas fa-info-circle" data-toggle="tooltip"
                                                              title="{{
                    __('Enabling this setting will render all enemies with a border to indicate their aggressiveness. Red for aggressive enemies, yellow for neutral enemies, green for friendly enemies etc.')
                     }}"></i>
                </label>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col pr-2">
                {!! Form::checkbox('map_settings_enemy_aggressiveness_border', 1, $mapEnemyAggressivenessBorder, [
                    'id' => 'map_settings_enemy_aggressiveness_border',
                    'class' => 'form-control left_checkbox'
                    ]) !!}
            </div>
        </div>
    </div>

    <!-- Dangerous enemies -->
    <div class="form-group">
        <div class="row no-gutters">
            <div class="col pr-2">
                <label for="map_settings_enemy_dangerous_border">
                    {{ __('Highlight dangerous enemies') }} <i class="fas fa-info-circle" data-toggle="tooltip"
                                                              title="{{
                    __('Dangerous enemies are marked with a dotted orange inner border. These enemies are hand-picked by Keystone.guru. 
                        These enemies are mini-bosses, those with high health compared to others, use dangerous abilities or otherwise require special care.')
                     }}"></i>
                </label>
            </div>
        </div>
        <div class="row no-gutters">
            <div class="col pr-2">
                {!! Form::checkbox('map_settings_enemy_dangerous_border', 1, $mapEnemyDangerousBorder, [
                    'id' => 'map_settings_enemy_dangerous_border',
                    'class' => 'form-control left_checkbox'
                    ]) !!}
            </div>
        </div>
    </div>

    <h4 class="mt-4">{{ __('Drawing') }}</h4>
    <!-- Default line weight -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <label for="edit_route_freedraw_options_weight">
                    {{ __('Default line weight') }} <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                    __('This controls the default weight (width) of any lines you create on the map, such as paths and free drawn lines.')
                     }}"></i>
                </label>
            </div>
        </div>
        <div class="row">
            <div class="col line_weight_selection">
                <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                {!! Form::select('edit_route_freedraw_options_weight', [1, 2, 3, 4, 5],
                    isset($_COOKIE['polyline_default_weight']) ? $_COOKIE['polyline_default_weight'] : 0,
                    ['id' => 'edit_route_freedraw_options_weight', 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
    </div>

    {{--    <div class="form-group mb-0">--}}
    {{--        <button id="save_draw_settings" class="offset-lg-4 col-lg-4 btn btn-success">--}}
    {{--            <i class="fas fa-save"></i> {{ __('Save settings') }}--}}
    {{--        </button>--}}
    {{--        <button id="save_map_settings_saving" class="offset-lg-5 col-lg-2 btn btn-success disabled"--}}
    {{--                style="display: none;">--}}
    {{--            <i class="fas fa-circle-notch fa-spin"></i>--}}
    {{--        </button>--}}
    {{--    </div>--}}
</div>