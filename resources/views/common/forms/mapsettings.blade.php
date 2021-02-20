<div class="draw_settings_tools">
    <?php // Weight ?>
    <div class="form-group">
        <div class="row view_dungeonroute_details_row">
            <div class="col">
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