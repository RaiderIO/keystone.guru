<div class="draw_settings_tools">
    <?php // Weight ?>
    <div class="form-group">
        <div class="row view_dungeonroute_details_row">
            <div class="col font-weight-bold">
                {{ __('Default line weight') }} <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                    __('This controls the default weight (width) of any lines you create on the map, such as paths and free drawn lines.')
                     }}"></i>
            </div>
        </div>
        <div class="row view_dungeonroute_details_row">
            <div class="col line_weight_selection">
                <?php // Select floor thing is a place holder because otherwise the selectpicker will complain on an empty select ?>
                {!! Form::select('edit_route_freedraw_options_weight', [1, 2, 3, 4, 5],
                    isset($_COOKIE['polyline_default_weight']) ? $_COOKIE['polyline_default_weight'] : 0,
                    ['id' => 'edit_route_freedraw_options_weight', 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row view_dungeonroute_details_row mt-2">
            <div class="col font-weight-bold">
                {{ __('Pull gradient') }} <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                    __('Setting a pull gradient will allow Keystone.guru to automatically color your pulls along a gradient.
                    Using this feature you can more easily see which pull belongs to which part of the route, useful for non-linear routes alike. This setting is unique per route.')
                     }}"></i>
            </div>
        </div>
        <div class="row view_dungeonroute_details_row mt-3">
            <div id="edit_route_freedraw_options_gradient" class="col-10">

            </div>
            <div class="col-2">
                <button id="edit_route_freedraw_options_gradient_apply_to_pulls" class="btn btn-success"
                        data-toggle="tooltip" title="{{ __('Apply to current pulls') }}">
                    {{ __('Apply') }}
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
                    {{ __('Always apply when I change pulls') }} <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                    __('Enabling this setting will update your pull\'s colors as you edit your pulls based on the pull gradient configured above. This setting is unique per route.')
                     }}"></i>
                </label>
            </div>
        </div>
        <div class="row no-gutters view_dungeonroute_details_row">
            <div class="col pr-2">
                {!! Form::checkbox('pull_gradient_apply_always', 1,
                    isset($model) ? $model->pull_gradient_apply_always : false,
                    ['id' => 'pull_gradient_apply_always', 'class' => 'form-control left_checkbox'])
                    !!}
            </div>
        </div>
    </div>

    <div class="form-group mb-0">
        <button id="save_draw_settings" class="offset-lg-4 col-lg-4 btn btn-success">
            <i class="fas fa-save"></i> {{ __('Save') }}
        </button>
        <button id="save_draw_settings_saving" class="offset-lg-5 col-lg-2 btn btn-success disabled"
                style="display: none;">
            <i class="fas fa-circle-notch fa-spin"></i> {{ __('Save') }}
        </button>
    </div>
</div>