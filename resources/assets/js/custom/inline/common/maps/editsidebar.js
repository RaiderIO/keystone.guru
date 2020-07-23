class CommonMapsEditsidebar extends InlineCode {
    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);

        this._colorPicker = null;
        this._grapick = null;

        getState().register('focusedenemy:changed', this, this._onFocusedEnemyChanged.bind(this));
    }

    /**
     *
     */
    activate() {
        super.activate();

        this.sidebar.activate();

        let self = this;

        // Copy to clipboard functionality
        $('#map_copy_to_clipboard').bind('click', function () {
            // https://codepen.io/shaikmaqsood/pen/XmydxJ
            let $temp = $("<input>");
            $("body").append($temp);
            $temp.val($('#map_shareable_link').val()).select();
            document.execCommand("copy");
            $temp.remove();

            showInfoNotification(lang.get('messages.copied_to_clipboard'));
        });

        // Setup line weight
        let $weight = $('#edit_route_freedraw_options_weight');
        $weight.bind('change', function (changeEvent) {
            let weight = $('#edit_route_freedraw_options_weight :selected').val();

            c.map.polyline.defaultWeight = weight;

            Cookies.set('polyline_default_weight', weight);

            self.map.refreshPather();

            self.addControl();
        });
        // -1 for value to index conversion
        $weight.val(c.map.polyline.defaultWeight - 1);

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

            $('.draw_settings_tools .pickr').addClass('grapick_color_picker_button grapick_color_picker_button_outer');
            $('.draw_settings_tools .pcr-button').addClass('grapick_color_picker_button');
        });

        // Restore pull_gradient if set
        let handlers = this._getHandlersFromCookie();
        for (let index in handlers) {
            if (handlers.hasOwnProperty(index)) {
                this._grapick.addHandler(handlers[index][0], handlers[index][1]);
            }
        }

        // Do stuff on change of the gradient
        this._grapick.on('change', complete => {
            // construct pull_gradient string from handlers
            let pullGradient = [];
            for (let i = 0; i < self._grapick.getHandlers().length; i++) {
                let handler = self._grapick.getHandler(i);
                pullGradient.push(handler.position + ' ' + self._parseHandlerColor(handler.color));
            }
            let result = pullGradient.join(',');
            Cookies.set('pull_gradient', result);
        });

        $('#edit_route_freedraw_options_gradient_apply_to_pulls').bind('click', function () {
            let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

            let count = killZoneMapObjectGroup.objects.length;
            for (let i = 0; i < count; i++) {
                for (let killZoneIndex in killZoneMapObjectGroup.objects) {
                    if (killZoneMapObjectGroup.objects.hasOwnProperty(killZoneIndex)) {
                        let killZone = killZoneMapObjectGroup.objects[killZoneIndex];
                        if (killZone.getIndex() === (i + 1)) {
                            // Prevent division by 0
                            killZone.color = pickHexFromHandlers(self._getHandlersFromCookie(), count === 1 ? 50 : (i / count) * 100);
                            killZone.setSynced(true);
                            break;
                        }
                    }
                }
            }

            killZoneMapObjectGroup.saveAll();
        });
    }

    /**
     * Called when the focused enemy was changed
     * @param focusedEnemyChangedEvent
     * @private
     */
    _onFocusedEnemyChanged(focusedEnemyChangedEvent) {

        let isNull = focusedEnemyChangedEvent.data.focusedenemy === null;
        // Show/hide based on being set or not
        $('#enemy_info_container').toggle(!isNull);
        if (!isNull) {
            // Update the focused enemy in the sidebar
            let template = Handlebars.templates['map_sidebar_enemy_info_template'];

            $('#enemy_info_key_value_container').html(
                template(focusedEnemyChangedEvent.data.focusedenemy.getVisualData())
            )
        }
    }

    /**
     * Fetches a handler structure from a cookie
     * @returns {[]}
     * @private
     */
    _getHandlersFromCookie() {
        let result = [];

        let pullGradient = Cookies.get('pull_gradient');
        if (typeof pullGradient !== 'undefined' && pullGradient.length > 0) {
            let handlers = pullGradient.split(',');
            for (let index in handlers) {
                if (handlers.hasOwnProperty(index)) {
                    let handler = handlers[index];
                    let values = handler.trim().split(' ');
                    // Only RGB values
                    if (values[1].indexOf('#') === 0) {
                        result.push([parseInt(('' + values[0]).replace('%', '')), values[1]]);
                    } else {
                        console.warn('Invalid handler found:', handler);
                    }
                }
            }
        } else {
            result = c.map.editsidebar.pullGradient.defaultHandlers;
        }

        return result;
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

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
        getState().unregister('focusedenemy:changed', this);
    }
}