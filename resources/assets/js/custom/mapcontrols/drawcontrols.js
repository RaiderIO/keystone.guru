// The mode handlers are generated from the editor's tool list (DrawControls#_getTools()), passed in as `tools`.
L.DrawToolbar.prototype.getModeHandlers = function (map) {
    return (this.options.tools ?? []).map((tool) => {
        let handler = new tool.handler(map, this.options[tool.id]);
        console.assert(handler.type === tool.id, 'Draw tool id must match its handler type', tool.id, handler.type);

        return {
            enabled: this.options[tool.id],
            handler: handler,
            title: this.options[tool.id].title
        };
    });
};

// Prevent the creation of new vertices during edit for layers that opted out (arrows, which are
// always exactly two vertices). Every other polyline keeps Leaflet.draw's middle markers - dragging
// one is the only way to insert a vertex into an existing line (#3966).
const _originalCreateMiddleMarker = L.Edit.PolyVerticesEdit.prototype._createMiddleMarker;
L.Edit.PolyVerticesEdit.prototype._createMiddleMarker = function (marker1, marker2) {
    if (this._poly.options.allowVertexCreationDuringEdit === false) {
        return;
    }
    _originalCreateMiddleMarker.call(this, marker1, marker2);
};

// Add some new strings to the draw controls
// https://github.com/Leaflet/Leaflet.draw#customizing-language-and-text-in-leafletdraw
L.drawLocal = $.extend(L.drawLocal, lang.messages[`${lang.locale}.leafletdraw`]);

class DrawControls extends MapControl {
    constructor(map, editableItemsLayer) {
        super(map);
        console.assert(this instanceof DrawControls, 'this is not DrawControls', this);
        console.assert(map instanceof DungeonMap, 'map is not DungeonMap', map);
        console.assert(editableItemsLayer instanceof L.FeatureGroup, 'editableItemsLayer is not L.FeatureGroup', editableItemsLayer);

        let self = this;

        this._mapControl = null;
        this.editableItemsLayer = editableItemsLayer;
        this.drawControlOptions = {};
        this.drawControlSnackbarId = null;

        // Add a created item to the list of drawn items
        this.map.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            self.editableItemsLayer.addLayer(event.layer);
        });

        // Make sure that when pather is toggled, the button changes state accordingly
        this.map.register('map:mapstatechanged', this, function (toggleEvent) {
            let enabled = toggleEvent.data.newMapState instanceof PatherMapState;
            let $brushlineButton = $('.leaflet-draw-draw-brushline');

            // Show or hide draw actions depending on what was needed
            let $drawActions = $('.leaflet-draw-actions-pather');
            $drawActions.toggle(enabled);

            // Enable/disable the button accordingly
            if (enabled) {
                $brushlineButton.addClass('leaflet-draw-toolbar-button-enabled');
            } else {
                $brushlineButton.removeClass('leaflet-draw-toolbar-button-enabled');
            }
        });

        this.map.register('map:pathertoggled', this, function (toggleEvent) {
            // If it was disabled - remove the current snackbar
            if (!toggleEvent.data.enabled && self.drawControlSnackbarId !== null) {
                // Delete the snackbar including .leaflet-draw-actions-pather - we don't need it anymore and will just re-create it
                getState().removeSnackbar(self.drawControlSnackbarId);
                self.drawControlSnackbarId = null;
            }
        });

        this._attachHotkeys();

        // Remove delete all button -> https://stackoverflow.com/a/46949925
        L.EditToolbar.Delete.include({
            removeAllLayers: false
        });
    }

    /**
     * The tools of this editor in toolbar order. The Leaflet.draw mode handlers and options, the toolbar buttons and
     * the hotkeys are all generated from this list.
     *
     * @returns {DrawTool[]}
     * @protected
     */
    _getTools() {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);

        return [{
            id: 'path',
            icon: 'fa-route',
            label: 'js.path',
            title: 'js.path_title',
            keys: ['1', 'p'],
            handler: L.Draw.Path,
            options: {
                shapeOptions: {
                    color: c.map.polyline.defaultColor(),
                    weight: c.map.polyline.defaultWeight,
                    opacity: 1.0
                },
                zIndexOffset: 1000,
            },
        }, {
            id: 'killzone',
            hidden: true,
            handler: L.Draw.KillZone,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'mapicon',
            icon: 'fa-icons',
            label: 'js.mapicon',
            title: 'js.mapicon_title',
            keys: ['2', 'i'],
            handler: L.Draw.MapIcon,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'awakenedobeliskgatewaymapicon',
            hidden: true,
            handler: L.Draw.AwakenedObeliskGatewayMapIcon,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'brushline',
            kind: 'pather',
            icon: 'fa-paint-brush',
            label: 'js.brushline',
            title: 'js.brushline_title',
            keys: ['3', 'b'],
        }, {
            id: 'arrow',
            icon: 'fa-arrow-right',
            label: 'js.arrow',
            title: 'js.arrow_title',
            keys: ['4', 'r'],
            handler: L.Draw.Arrow,
            options: {
                shapeOptions: {
                    color: c.map.polyline.defaultColor(),
                    weight: c.map.polyline.defaultWeight,
                    opacity: 1.0
                },
                zIndexOffset: 1000,
            },
        }, {
            id: 'edit',
            kind: 'edit',
            icon: 'fa-edit',
            label: 'js.edit',
            title: 'js.edit_title',
            keys: ['5', 'e'],
        }, {
            id: 'delete',
            kind: 'remove',
            icon: 'fa-trash',
            label: 'js.delete',
            title: 'js.delete_title',
            keys: ['6', 'x'],
            btnType: 'btn-danger',
        }];
    }

    /**
     * @protected
     */
    _attachHotkeys() {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);

        this.map.hotkeys.setTools(this._getTools());
    }

    /**
     * @param tool {DrawTool}
     * @returns {String} Every key of the tool, for in a tooltip
     * @private
     */
    _getToolHotkeyText(tool) {
        return (tool.keys ?? []).map((chord) => Hotkeys.formatChord(chord)).join(' / ');
    }

    /**
     * @param tool {DrawTool}
     * @returns {String} The key shown on the tool's button
     * @private
     */
    _getToolKeycap(tool) {
        return (tool.keys ?? []).length > 0 ? Hotkeys.formatChord(tool.keys[0]) : '';
    }

    /**
     * Gets the newly generated options for the drawing control.
     * @param tools {DrawTool[]}
     * @returns object
     * @protected
     */
    _getDrawControlOptions(tools) {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);

        let drawTools = tools.filter((tool) => (tool.kind ?? 'draw') === 'draw');
        let draw = {tools: drawTools};
        for (let tool of drawTools) {
            draw[tool.id] = $.extend({}, tool.options, {
                title: tool.hidden ? '' : lang.get(tool.title, {hotkey: this._getToolHotkeyText(tool)}),
            });
        }

        return {
            position: 'topleft',
            draw: draw,
            edit: {
                featureGroup: this.editableItemsLayer, //REQUIRED!!
                remove: true
            }
        }
    }

    /**
     * Get HTML that should be placed inside a button that is used for interaction with the route.
     * @param faIconClass {String}
     * @param text {String}
     * @param hotkey {String}
     * @param title {String}
     * @param btnType {String}
     * @returns {String}
     * @private
     */
    _getButtonHtml(faIconClass, text, hotkey = '', title = '', btnType = '') {
        let template = Handlebars.templates['map_controls_route_edit_button_template'];

        let data = {
            fa_class: faIconClass,
            text: text,
            hotkey: hotkey,
            title: title,
            btnType: btnType
        };

        return template(data);
    }

    _addControlSetupBottomBar() {
        let self = this;

        let container = this._mapControl.getContainer();
        let $targetContainer = $('#edit_route_draw_container');
        $targetContainer.append(container);

        // Now that the container is added, modify it to look the way we want it to
        let $container = $(container);
        // remove all classes
        $container.removeClass();
        $container.addClass('container p-0');

        $.each($container.children(), function (i, child) {
            $(child).removeClass();
        });

        let $originalDrawActions = $container.find('.leaflet-draw-actions');

        this.map.leafletMap.off(L.Draw.Event.TOOLBAROPENED).on(L.Draw.Event.TOOLBAROPENED, function (e) {
            // Ensure that pather is disabled now
            self.map.togglePather(false);

            self._refreshToolGroups();

            // Put the draw actions in a different div
            let $drawActions = $container.find('.leaflet-draw-actions');
            $originalDrawActions.removeClass('row g-0').addClass('row g-0')
                .find('li').removeClass('col btn btn-info mx-2 p-0').addClass('col btn btn-info mx-2 p-0')
                .find('a').removeClass('d-inline-block w-100 h-100').addClass('d-inline-block w-100 h-100');

            $drawActions.css('top', '');

            // Don't change the display of those who have display: none;
            $drawActions.each(function (index, elem) {
                let $elem = $(elem);
                if ($elem.is(':visible')) {
                    // Should not be block but inherit from the clsses instead (which will be flex)
                    $elem.css('display', '');
                }
            });

            // Add it to an empty snackbar - but copy the DOM over on render time so that we preserve all the Leaflet.draw events
            self.drawControlSnackbarId = getState().addSnackbar('', {
                onDomAdded: function (id) {
                    $(`#${id}`).append(
                        $drawActions
                    );
                }
            });
        });

        this.map.leafletMap.off(L.Draw.Event.TOOLBARCLOSED).on(L.Draw.Event.TOOLBARCLOSED, function (e) {
            // Fired before Leaflet.draw takes the active class off the closed tool's button
            $container.find('.draw_tool_group_button').removeClass('leaflet-draw-toolbar-button-enabled');

            let snackbar = $(`#${self.drawControlSnackbarId}`);

            if (snackbar.length > 0) {
                // Restore the draw actions to the previous container - storing it for future use
                let $drawActions = snackbar.find('.leaflet-draw-actions');
                $container.append(
                    $drawActions
                );
                // Delete the now empty snackbar
                getState().removeSnackbar(self.drawControlSnackbarId);
                self.drawControlSnackbarId = null;
            }
        });
    }

    /**
     * @returns {jQuery} The button that toggles Pather, which is not a Leaflet.draw tool
     * @private
     */
    _createBrushlineButton() {
        let self = this;

        // Add a special button for the Brushline
        let $brushlineButton = $('<a>', {
            class: 'leaflet-draw-draw-brushline mt-2' +
                // If pather was enabled, make sure it stays active
                (self.map.getMapState() instanceof PatherMapState ? ' leaflet-draw-toolbar-button-enabled' : ''),
            href: '#',
        });

        $brushlineButton.unbind('click').bind('click', function () {
            // Check if it's enabled now
            let wasEnabled = self.map.getMapState() instanceof PatherMapState;
            // Don't do anything to make it consistent with other draw tools. Cancel with escape, not hitting the key again
            if (wasEnabled) {
                return;
            }
            // Enable it now
            self.map.togglePather(true);

            // Check if we were drawing anything else at this point, otherwise click the cancel button
            let $mainDrawActions = $('.leaflet-draw-actions:not(.leaflet-draw-actions-pather):visible');
            // Physically click the button
            let $a = $mainDrawActions.find('a');
            if ($a.length > 0) {
                // Cancel is always the last button
                $a.last()[0].click();
            }

            // Finished button container
            let $drawActions = $('<ul>', {
                class: 'leaflet-draw-actions-pather leaflet-draw-actions leaflet-draw-actions-bottom row g-0',
            });
            // Create the button
            let $button = $('<a>', {
                href: '#',
                class: 'd-inline-block w-100 h-100',
                'data-bs-toggle': 'tooltip',
                'data-bs-placement': 'right',
                title: lang.get('js.finish_drawing'),
                text: lang.get('js.finish')
            });

            // On click, disable pather
            $button.unbind('click').bind('click', function () {
                self.map.togglePather(false);
            });

            // Build the draw actions
            $drawActions.append($('<li>', {
                class: 'col btn btn-info p-0'
            }).append($button));

            self.drawControlSnackbarId = getState().addSnackbar('', {
                onDomAdded: function (id) {
                    $(`#${id}`).append(
                        $drawActions
                    );
                }
            });
        });

        return $brushlineButton;
    }

    /**
     * @param $container {jQuery}
     * @param tool {DrawTool}
     * @returns {jQuery}
     * @private
     */
    _findToolButton($container, tool) {
        switch (tool.kind ?? 'draw') {
            case 'pather':
                return this._createBrushlineButton();
            case 'edit':
                return $container.find('.leaflet-draw-edit-edit');
            case 'remove':
                return $container.find('.leaflet-draw-edit-remove');
            default:
                return $container.find(`.leaflet-draw-draw-${tool.id}`);
        }
    }

    /**
     * @param group {String}
     * @param firstTool {DrawTool}
     * @returns {jQuery}
     * @private
     */
    _createToolGroup(group, firstTool) {
        let self = this;
        let template = Handlebars.templates['map_controls_draw_tool_group_template'];

        let $group = $(template({
            group: group,
            fa_class: firstTool.icon,
            label: lang.get(`js.draw_tool_group_${group}`),
        }));

        $group.find('.draw_tool_group_button').on('click', function (clickEvent) {
            clickEvent.preventDefault();

            let $flyout = $group.find('.draw_tool_group_flyout');
            let wasOpen = $flyout.is(':visible');
            self._closeToolGroups();
            if (!wasOpen) {
                $flyout.show();
                $(this).attr('aria-expanded', 'true');
            }
        });

        return $group;
    }

    /**
     * @private
     */
    _closeToolGroups() {
        $('.draw_tool_group_flyout').hide();
        $('.draw_tool_group_button').attr('aria-expanded', 'false');
    }

    /**
     * Marks the group of the active tool as active.
     * @private
     */
    _refreshToolGroups() {
        $('.draw_tool_group').each(function (index, group) {
            let $group = $(group);
            $group.find('.draw_tool_group_button').toggleClass(
                'leaflet-draw-toolbar-button-enabled',
                $group.find('.draw_tool_group_flyout .leaflet-draw-toolbar-button-enabled').length > 0
            );
        });
    }

    /**
     * Renders every tool's button in the order of the tool list; grouped tools go in their group's flyout.
     * @param tools {DrawTool[]}
     * @private
     */
    _addControlSetupToolButtons(tools) {
        let self = this;

        let $container = $(this._mapControl.getContainer());
        let $buttonContainer = $($container.children()[0]);
        let $editRouteControls = $($container.children()[1]);

        // Add some padding for the above custom controls
        $editRouteControls.css('height', '0');

        let flyoutItemTemplate = Handlebars.templates['map_controls_draw_tool_flyout_item_template'];
        let $groups = {};

        for (let tool of tools) {
            let $button = this._findToolButton($container, tool);
            $button.attr('data-draw-tool', tool.id).removeAttr('title');

            if (tool.hidden) {
                $buttonContainer.append($button.addClass('d-none'));
                continue;
            }

            let label = lang.get(tool.label);
            $button.attr('aria-label', label).css('background-image', 'none');

            if (!tool.group) {
                $button.addClass('draw_icon').html(
                    this._getButtonHtml(
                        tool.icon,
                        label,
                        this._getToolKeycap(tool),
                        lang.get(tool.title, {hotkey: this._getToolHotkeyText(tool)}),
                        tool.btnType ?? ''
                    )
                );
                $buttonContainer.append($button);
                continue;
            }

            if (!$groups.hasOwnProperty(tool.group)) {
                $groups[tool.group] = this._createToolGroup(tool.group, tool);
                $buttonContainer.append($groups[tool.group]);
            }
            let $group = $groups[tool.group];

            $button.addClass('draw_tool_flyout_item').html(flyoutItemTemplate({
                fa_class: tool.icon,
                text: label,
                hotkey: this._getToolKeycap(tool),
            }));
            // Through a hotkey too, since that clicks the button
            $button.on('click', function () {
                $group.find('.draw_tool_group_icon').removeClass().addClass(`fas ${tool.icon} draw_tool_group_icon`);
                self._closeToolGroups();
            });
            $group.find('.draw_tool_group_flyout').append($button);
        }

        $(document).off('.drawtoolgroups')
            .on('click.drawtoolgroups', function (clickEvent) {
                if ($(clickEvent.target).closest('.draw_tool_group').length === 0) {
                    self._closeToolGroups();
                }
            })
            .on('keydown.drawtoolgroups', function (keyEvent) {
                if (keyEvent.key === 'Escape') {
                    self._closeToolGroups();
                }
            });
    }

    /**
     * Adds the control to the map.
     */
    addControl() {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);

        // Remove if exists
        if (this._mapControl !== null) {
            this.map.leafletMap.removeControl(this._mapControl);
        }

        let tools = this._getTools();

        // Add the control to the map
        this.drawControlOptions = this._getDrawControlOptions(tools);
        this._mapControl = new L.Control.Draw(this.drawControlOptions);
        this.map.leafletMap.addControl(this._mapControl);

        // Add the leaflet draw control to the bottom bar
        this._addControlSetupBottomBar();

        this._addControlSetupToolButtons(tools);

        // Re-set pather to the same enabled state so all events are fired and UI is put back in a proper state
        if (tools.some((tool) => tool.kind === 'pather')) {
            this.map.togglePather(this.map.getMapState() instanceof PatherMapState);
        }

        // Now done by the dungeonmap at the end of refresh
        // refreshTooltips();
        // refreshSelectPickers();
    }

    cleanup() {
        super.cleanup();

        $(document).off('.drawtoolgroups');

        // this.map.leafletMap.off(L.Draw.Event.CREATED);
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {DrawControls};
}
