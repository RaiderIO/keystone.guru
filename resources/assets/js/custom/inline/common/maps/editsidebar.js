class CommonMapsEditsidebar extends InlineCode {
    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);

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
        $('#map_shareable_link_copy_to_clipboard').bind('click', function () {
            copyToClipboard($('#map_shareable_link').val());
        });
        $('#map_embedable_link_copy_to_clipboard').bind('click', function () {
            copyToClipboard($('#map_embedable_link').val());
        });
        $('#map_mdt_export').bind('click', function () {
            self._fetchMdtExportString();
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
        let handlers = getState().getPullGradientHandlers();
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

            getState().getMapContext().setPullGradient(result);
        });

        $('#edit_route_freedraw_options_gradient_apply_to_pulls').bind('click', function () {
            $('#edit_route_freedraw_options_gradient_apply_to_pulls').hide();
            $('#edit_route_freedraw_options_gradient_apply_to_pulls_saving').show();

            let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

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
        });

        // Draw settings save button
        $('#save_draw_settings').bind('click', this._savePullGradientSettings.bind(this));

        $('#userreport_enemy_modal_submit').bind('click', this._submitEnemyUserReport.bind(this));
    }

    /**
     * Called when the focused enemy was changed
     * @param focusedEnemyChangedEvent
     * @private
     */
    _onFocusedEnemyChanged(focusedEnemyChangedEvent) {
        let focusedEnemy = focusedEnemyChangedEvent.data.focusedenemy;
        let isNull = focusedEnemy === null;
        // Show/hide based on being set or not
        // $('#enemy_info_container').toggle(!isNull);
        if (!isNull) {
            let visualData = focusedEnemy.getVisualData();
            if (visualData !== null) {
                $('#enemy_info_container').show().find('.card-title').html(focusedEnemy.npc.name);

                // Update the focused enemy in the sidebar
                let template = Handlebars.templates['map_sidebar_enemy_info_template'];

                $('#enemy_info_key_value_container').html(
                    template(visualData)
                );
                $('#enemy_report_enemy_id').val(focusedEnemy.id);
            }
        }
    }

    /**
     *
     * @private
     */
    _submitEnemyUserReport() {
        let enemyId = $('#enemy_report_enemy_id').val();

        $.ajax({
            type: 'POST',
            url: `/ajax/userreport/enemy/${enemyId}`,
            dataType: 'json',
            data: {
                category: $('#enemy_report_category').val(),
                username: $('#enemy_report_username').val(),
                message: $('#enemy_report_message').val(),
                contact_ok: $('#enemy_report_contact_ok').is(':checked') ? 1 : 0
            },
            beforeSend: function () {
                $('#userreport_enemy_modal_submit').hide();
                $('#userreport_enemy_modal_saving').show();
            },
            success: function (json) {
                $('#userreport_enemy_modal').modal('hide');
                showSuccessNotification(lang.get('messages.user_report_enemy_success'));
            },
            complete: function () {
                $('#userreport_enemy_modal_submit').show();
                $('#userreport_enemy_modal_saving').hide();
            }
        });
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
                $('#save_draw_settings').hide();
                $('#save_draw_settings_saving').show();
            },
            success: function (json) {
                showSuccessNotification(lang.get('messages.pull_gradient_settings_saved'));
            },
            complete: function () {
                $('#save_draw_settings').show();
                $('#save_draw_settings_saving').hide();
            }
        });
    }

    _fetchMdtExportString() {
        $.ajax({
            type: 'GET',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/mdtExport`,
            dataType: 'json',
            beforeSend: function () {
                $('#mdt_export_loader_container').show();
                $('#mdt_export_result_container').hide();
            },
            success: function (json) {
                $('#mdt_export_result').val(json.mdt_string);
            },
            complete: function () {
                $('#mdt_export_loader_container').hide();
                $('#mdt_export_result_container').show();
            }
        });
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
        getState().unregister('focusedenemy:changed', this);
    }
}