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

        // Zoom speed
        $('#map_settings_zoom_speed').bind('change', function () {
            getState().setMapZoomSpeed(self._setSliderValueToDom(this));
        });

        // Heatmap show tooltips
        $('#map_settings_heatmap_show_tooltips').bind('change', function () {
            getState().setHeatmapShowTooltips($(this).is(':checked'));
        });

        // Heatmap render order
        $('#map_settings_heatmap_show_on_top').bind('change', function () {
            getState().setHeatmapShowOnTop($(this).is(':checked'));
        });

        // Unkilled enemy opacity
        $('#map_settings_unkilled_enemy_opacity').bind('change', function () {
            getState().setUnkilledEnemyOpacity(self._setSliderValueToDom(this));
        });

        // Unkilled important enemy opacity
        $('#map_settings_unkilled_important_enemy_opacity').bind('change', function () {
            getState().setUnkilledImportantEnemyOpacity(self._setSliderValueToDom(this));
        });

        // Enemy aggressiveness border
        $('#map_settings_enemy_aggressiveness_border').bind('change', function () {
            getState().setEnemyAggressivenessBorder($(this).is(':checked'));
        });

        // Enemy dangerous border
        $('#map_settings_enemy_dangerous_border').bind('change', function () {
            getState().setEnemyDangerousBorder($(this).is(':checked'));
        });

        // Killzone path stroke width
        $('#map_settings_kill_zone_path_weight').bind('change', function () {
            let weight = self._setSliderValueToDom(this);
            getState().setKillZonePathWeight(weight);

            let user = getState().getUser();
            if (user !== null) {
                $.ajax({
                    type: 'PUT',
                    url: `/ajax/user/${user.public_key}`,
                    dataType: 'json',
                    data: {
                        kill_zone_path_weight: weight,
                        _method: 'PATCH'
                    }
                });
            }
        });
    }

    _setSliderValueToDom(context) {
        let $this = $(context);
        let value = parseInt($this.val());
        $this.closest('.row').find('.value').text(value);
        return value;
    }
}
