class SettingsTabPull extends SettingsTab {

    constructor(options) {
        super(options);

    }

    activate() {

        let self = this;

        if (this.options.dungeonroute !== null && $('#edit_route_freedraw_options_gradient').length > 0) {
            // Gradient setup
            this._grapick = new Grapick({
                el: '#edit_route_freedraw_options_gradient',
                colorEl: '<div id="grapick_color_picker" class="handler-color-wrap"></div>'
            });
            this._grapick.setColorPicker(handler => {
                let defaultColor = self._parseHandlerColor(handler.getColor());
                Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
                    el: `#grapick_color_picker`,
                    // Convert if necessary
                    default: defaultColor
                })).on('save', (color, instance) => {
                    let newColor = '#' + color.toHEXA().join('');
                    // Apply the new color
                    handler.setColor(newColor);

                    // Reset ourselves
                    instance.hide();
                });

                $('.pull_settings_tools .pickr').addClass('grapick_color_picker_button grapick_color_picker_button_outer');
                $('.pull_settings_tools .pcr-button').addClass('grapick_color_picker_button');
            });

            // Restore pull_gradient if set
            let handlers = getState().getPullGradientHandlers();
            for (let index in handlers) {
                if (handlers.hasOwnProperty(index)) {
                    this._grapick.addHandler(handlers[index][0], handlers[index][1]);
                }
            }

            // Do stuff on change of the gradient
            let onChange = complete => {
                // construct pull_gradient string from handlers
                let pullGradient = [];
                for (let i = 0; i < self._grapick.getHandlers().length; i++) {
                    let handler = self._grapick.getHandler(i);
                    pullGradient.push(SettingsTabPull.formatHandlerPosition(handler.position) + ' ' + self._parseHandlerColor(handler.color));
                }
                let result = pullGradient.join(',');

                getState().getMapContext().setPullGradient(result);

                self._savePullGradientSettings();
            };

            this._grapick.on('handler:drag:end', onChange);
            this._grapick.on('handler:add', onChange);
            this._grapick.on('handler:remove', onChange);
            this._grapick.on('handler:color:change', onChange);

            $('#edit_route_freedraw_options_gradient_apply_to_pulls').unbind('click').bind('click', function () {
                $('#edit_route_freedraw_options_gradient_apply_to_pulls').hide();
                $('#edit_route_freedraw_options_gradient_apply_to_pulls_saving').show();

                let killZoneMapObjectGroup = getKillZoneMapObjectGroup();

                killZoneMapObjectGroup.applyPullGradient(true, function () {
                    $('#edit_route_freedraw_options_gradient_apply_to_pulls').show();
                    $('#edit_route_freedraw_options_gradient_apply_to_pulls_saving').hide();
                });
            });

            let $alwaysApplyPullGradient = $('#pull_gradient_apply_always');
            let alwaysApplyPullGradient = getState().getMapContext().getPullGradientApplyAlways();
            if (alwaysApplyPullGradient) {
                $alwaysApplyPullGradient.attr('checked', 'checked');
            } else {
                $alwaysApplyPullGradient.removeAttr('checked');
            }
            $alwaysApplyPullGradient.bind('change', function () {
                getState().getMapContext().setPullGradientApplyAlways($(this).is(':checked'));

                self._savePullGradientSettings();
            });
        }
    }

    /**
     * Formats a handler position as a plain decimal within 0-100. Grapick's drag arithmetic leaves float
     * residue such as 2.220446049250313e-16, which String() would render in exponent notation.
     * @param position {Number}
     * @returns {String}
     */
    static formatHandlerPosition(position) {
        let clamped = Math.min(100, Math.max(0, Number(position) || 0));

        return clamped.toFixed(4).replace(/\.?0+$/, '');
    }

    /**
     * Takes over the gradient as the server stored it, so the next save resends that canonical form.
     * @param json {{pull_gradient: String}|undefined}
     */
    static applySavedPullGradient(json) {
        if (json && typeof json.pull_gradient === 'string') {
            getState().getMapContext().setPullGradient(json.pull_gradient);
        }
    }

    /**
     * Parses a color from a handler, and return an #FF0000 hex color.
     * @param handlerColor
     * @returns {*}
     * @private
     */
    _parseHandlerColor(handlerColor) {
        return handlerColor.indexOf('rgba') === 0 ? rgbToHex(parseRgba(handlerColor)) : handlerColor;
    }

    /**
     *
     * @private
     */
    _savePullGradientSettings() {
        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/pullgradient`,
            dataType: 'json',
            data: {
                pull_gradient: getState().getMapContext().getPullGradient(),
                pull_gradient_apply_always: getState().getMapContext().getPullGradientApplyAlways() ? '1' : '0',
                _method: 'PATCH'
            },
            beforeSend: function () {
                $('#save_pull_settings').hide();
                $('#save_pull_settings_saving').show();
            },
            success: function (json) {
                SettingsTabPull.applySavedPullGradient(json);
                // showSuccessNotification(lang.get('js.pull_gradient_settings_saved'));
            },
            complete: function () {
                $('#save_pull_settings').show();
                $('#save_pull_settings_saving').hide();
            }
        });
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        SettingsTabPull,
    };
}
