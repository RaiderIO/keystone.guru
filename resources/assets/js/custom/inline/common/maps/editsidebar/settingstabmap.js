class SettingsTabMap extends SettingsTab {

    constructor(options) {
        super(options);

    }

    activate() {
        let self = this;

        // Setup line weight
        $('#edit_route_freedraw_options_weight').bind('change', function () {
            let weight = $('#edit_route_freedraw_options_weight :selected').val();

            c.map.polyline.defaultWeight = weight;

            Cookies.set('polyline_default_weight', weight, cookieDefaultAttributes);

            getState().getDungeonMap().refreshPather();
        })// -1 for value to index conversion
            .val(c.map.polyline.defaultWeight);

        // Setup color picker
        // Handle changes
        let $editRouteFreedrawOptionsColor = $('#edit_route_freedraw_options_color');
        if ($editRouteFreedrawOptionsColor.length > 0) {
            this._colorPicker = Pickr.create($.extend(true, {}, c.map.colorPickerDefaultOptions, {
                el: `#edit_route_freedraw_options_color`,
                default: Cookies.get('polyline_default_color'),
                components: {
                    interaction: {
                        clear: true
                    }
                }
            })).on('save', (color, instance) => {
                if (color === null) {
                    Cookies.set('polyline_default_color', null, cookieDefaultAttributes);
                } else {
                    Cookies.set('polyline_default_color', '#' + color.toHEXA().join(''), cookieDefaultAttributes);
                }

                getState().getDungeonMap().refreshPather();

                // Reset ourselves
                instance.hide();
            });

            $editRouteFreedrawOptionsColor.unbind('click').bind('click', function () {
                self._colorPicker.show();
            });
        }

        // Add a class to make it display properly
        $(`.view_dungeonroute_details_row .pickr .pcr-button`).addClass('h-100 w-100');

        // Map facade style
        $('#map_settings_map_facade_style').bind('change', function () {
            let newMapFacadeStyle = $(this).is(':checked') ? MAP_FACADE_STYLE_FACADE : MAP_FACADE_STYLE_SPLIT_FLOORS;
            getState().setMapFacadeStyle(newMapFacadeStyle);

            let user = getState().getUser();
            if (user !== null)
                $.ajax({
                    type: 'PUT',
                    url: `/ajax/user/${user.public_key}`,
                    dataType: 'json',
                    data: {
                        map_facade_style: newMapFacadeStyle,
                        _method: 'PATCH'
                    }
                });
        });

        // Zoom speed
        $('#map_settings_zoom_speed').bind('change', function () {
            getState().setMapZoomSpeed(parseInt($(this).val()));
        });

        // Heatmap show tooltips
        $('#map_settings_heatmap_show_tooltips').bind('change', function () {
            getState().setHeatmapShowTooltips($(this).is(':checked'));
        });

        // Unkilled enemy opacity
        $('#map_settings_unkilled_enemy_opacity').bind('change', function () {
            getState().setUnkilledEnemyOpacity(parseInt($(this).val()));
        });

        // Unkilled important enemy opacity
        $('#map_settings_unkilled_important_enemy_opacity').bind('change', function () {
            getState().setUnkilledImportantEnemyOpacity(parseInt($(this).val()));
        });

        // Enemy aggressiveness border
        $('#map_settings_enemy_aggressiveness_border').bind('change', function () {
            getState().setEnemyAggressivenessBorder($(this).is(':checked'));
        });

        // Enemy dangerous border
        $('#map_settings_enemy_dangerous_border').bind('change', function () {
            getState().setEnemyDangerousBorder($(this).is(':checked'));
        });
    }
}
