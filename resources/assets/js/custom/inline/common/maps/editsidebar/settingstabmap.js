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

            Cookies.set('polyline_default_weight', weight);

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
                    Cookies.set('polyline_default_color', null);
                } else {
                    Cookies.set('polyline_default_color', '#' + color.toHEXA().join(''));
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
