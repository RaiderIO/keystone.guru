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

// Leaflet.draw re-sets a native title on Edit and Delete on every layer change, which shows on top of their
// Bootstrap tooltip; it only marks them disabled with a class.
const _originalCheckDisabled = L.EditToolbar.prototype._checkDisabled;
L.EditToolbar.prototype._checkDisabled = function () {
    _originalCheckDisabled.call(this);
    for (let mode of Object.values(this._modes)) {
        mode.button.removeAttribute('title');
        mode.button.setAttribute('aria-disabled', L.DomUtil.hasClass(mode.button, 'leaflet-disabled') ? 'true' : 'false');
    }
};

/**
 * Prefixes each action button's text with a Font Awesome icon, picked by the action's callback since
 * Leaflet.draw gives its actions no other identity.
 *
 * @param actions {Object[]}
 * @param iconsByCallback {Map<Function, String>}
 * @returns {Object[]}
 */
function _withActionIcons(actions, iconsByCallback) {
    return actions.map((action) => {
        let icon = typeof action.callback === 'function' ? iconsByCallback.get(action.callback) : undefined;

        return icon === undefined ? action : {
            ...action,
            text: `<i class="fas ${icon} me-1" aria-hidden="true"></i>${action.text}`
        };
    });
}

const _originalDrawGetActions = L.DrawToolbar.prototype.getActions;
L.DrawToolbar.prototype.getActions = function (handler) {
    return _withActionIcons(_originalDrawGetActions.call(this, handler), new Map([
        [handler.completeShape, 'fa-check'],
        [handler.deleteLastVertex, 'fa-undo'],
        [this.disable, 'fa-times'],
    ]));
};

const _originalEditGetActions = L.EditToolbar.prototype.getActions;
L.EditToolbar.prototype.getActions = function (handler) {
    return _withActionIcons(_originalEditGetActions.call(this, handler), new Map([
        [this._save, 'fa-save'],
        [this.disable, 'fa-times'],
        [this._clearAllLayers, 'fa-trash'],
    ]));
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
        /** @type {DrawTool[]} */
        this._tools = [];
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
            $brushlineButton.attr('aria-pressed', enabled ? 'true' : 'false');
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
     * @returns {String} The tool's tooltip: what it does, then every key of the tool as a keycap
     * @private
     */
    _getToolTooltipHtml(tool) {
        let hotkeys = (tool.keys ?? []).map((chord) => Hotkeys.formatChord(chord));

        return lang.get(tool.title, {
            hotkey: hotkeys.length > 0 ?
                Handlebars.templates['map_controls_draw_tool_hotkeys_template']({hotkeys: hotkeys}) : '',
        });
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
     * @param tool {DrawTool}
     * @returns {String} The tool's label, plus its hotkeys when it has any
     * @private
     */
    _getToolAriaLabel(tool) {
        let label = lang.get(tool.label);

        return (tool.keys ?? []).length > 0 ?
            lang.get('js.draw_tool_aria_label', {label: label, hotkey: this._getToolHotkeyText(tool)}) : label;
    }

    /**
     * @param tool {DrawTool}
     * @returns {String} Every key of the tool, in aria-keyshortcuts syntax
     * @private
     */
    _getToolAriaKeyShortcuts(tool) {
        return (tool.keys ?? []).map((chord) => Hotkeys.formatChord(chord)).join(' ');
    }

    /**
     * @param tool {DrawTool|null}
     * @returns {String} The contents of the snackbar shown while the tool is active
     * @private
     */
    _getToolSnackbarHtml(tool) {
        let status = '';
        if (tool !== null && !tool.hidden) {
            let label = lang.get(tool.label);
            status = Handlebars.templates['map_controls_draw_tool_status_template']({
                status: lang.get('js.draw_tool_status', {label: label}),
                fa_class: tool.icon,
                text: label,
                hotkey: this._getToolKeycap(tool),
            });
        }

        return `<div class="draw_tool_snackbar">${status}</div>`;
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
     * @param btnType {String}
     * @returns {String}
     * @private
     */
    _getButtonHtml(faIconClass, text, hotkey = '', btnType = '') {
        let template = Handlebars.templates['map_controls_route_edit_button_template'];

        let data = {
            fa_class: faIconClass,
            text: text,
            hotkey: hotkey,
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

            self._refreshActiveTool();

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

            let activeToolId = $container.find('[data-draw-tool].leaflet-draw-toolbar-button-enabled').attr('data-draw-tool');

            // Add it to a snackbar naming the tool - but copy the DOM over on render time so that we preserve all the Leaflet.draw events
            self.drawControlSnackbarId = getState().addSnackbar(
                self._getToolSnackbarHtml(self._tools.find((tool) => tool.id === activeToolId) ?? null), {
                    onDomAdded: function (id) {
                        $(`#${id} .draw_tool_snackbar`).append(
                            $drawActions
                        );
                    }
                });
        });

        this.map.leafletMap.off(L.Draw.Event.TOOLBARCLOSED).on(L.Draw.Event.TOOLBARCLOSED, function (e) {
            // Fired before Leaflet.draw takes the active class off the closed tool's button
            $container.find('.draw_tool_group_button').removeClass('leaflet-draw-toolbar-button-enabled');
            $container.find('[data-draw-tool]').attr('aria-pressed', 'false');

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
            }).append(
                $('<i>', {class: 'fas fa-check me-1', 'aria-hidden': 'true'}),
                document.createTextNode(lang.get('js.finish'))
            );

            // On click, disable pather
            $button.unbind('click').bind('click', function () {
                self.map.togglePather(false);
            });

            // Build the draw actions
            $drawActions.append($('<li>', {
                class: 'col btn btn-info p-0'
            }).append($button));

            self.drawControlSnackbarId = getState().addSnackbar(
                self._getToolSnackbarHtml(self._tools.find((tool) => tool.kind === 'pather') ?? null), {
                    onDomAdded: function (id) {
                        $(`#${id} .draw_tool_snackbar`).append(
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

        let $groupButton = $group.find('.draw_tool_group_button');
        let $flyout = $group.find('.draw_tool_group_flyout');

        $groupButton.on('click', function (clickEvent) {
            clickEvent.preventDefault();

            let wasOpen = $flyout.is(':visible');
            self._closeToolGroups();
            if (!wasOpen) {
                // A click from Enter or Space has no click count
                self._openToolGroup($group, (clickEvent.originalEvent?.detail ?? 1) === 0);
            }
        }).on('keydown', function (keyEvent) {
            if (keyEvent.key === 'ArrowRight' || keyEvent.key === 'ArrowDown') {
                keyEvent.preventDefault();
                self._openToolGroup($group, true);
            }
        });

        $flyout.on('keydown', function (keyEvent) {
            let $items = self._getToolGroupItems($group);
            let index = $items.index(document.activeElement);
            let nextIndex;
            switch (keyEvent.key) {
                case 'ArrowDown':
                    nextIndex = (index + 1) % $items.length;
                    break;
                case 'ArrowUp':
                    nextIndex = (index - 1 + $items.length) % $items.length;
                    break;
                case 'Home':
                    nextIndex = 0;
                    break;
                case 'End':
                    nextIndex = $items.length - 1;
                    break;
                case 'Escape':
                case 'ArrowLeft':
                    // Escape closes the flyout only, it does not also cancel the active tool
                    keyEvent.preventDefault();
                    keyEvent.stopPropagation();
                    self._closeToolGroups();
                    $groupButton[0].focus();
                    return;
                default:
                    return;
            }
            keyEvent.preventDefault();
            $items[nextIndex].focus();
        });

        $group.on('focusout', function (focusEvent) {
            let target = focusEvent.relatedTarget;
            if (target instanceof Node && !$group[0].contains(target)) {
                self._closeToolGroups();
            }
        });

        return $group;
    }

    /**
     * @param $group {jQuery}
     * @returns {jQuery} The tool buttons in the group's flyout
     * @private
     */
    _getToolGroupItems($group) {
        return $group.find('.draw_tool_group_flyout [data-draw-tool]');
    }

    /**
     * @param $group {jQuery}
     * @param focusTool {Boolean} Move focus to the active tool of the group, or its first one
     * @private
     */
    _openToolGroup($group, focusTool) {
        this._closeToolGroups();

        let $groupButton = $group.find('.draw_tool_group_button');
        let $flyout = $group.find('.draw_tool_group_flyout');
        // Fixed, because the rail is a scroll container that would clip the flyout and scroll sideways to a focused tool
        let buttonRect = $groupButton[0].getBoundingClientRect();
        // The rail already stops at the header above it and at any ad reserved below it
        let railRect = $group.closest('.route_manipulation_tools')[0].getBoundingClientRect();
        $flyout.css({top: buttonRect.top, left: buttonRect.right}).show();
        let overflowBottom = buttonRect.top + $flyout[0].getBoundingClientRect().height - railRect.bottom;
        if (overflowBottom > 0) {
            $flyout.css('top', Math.max(railRect.top, buttonRect.top - overflowBottom));
        }
        $groupButton.attr('aria-expanded', 'true');
        if (typeof bootstrap !== 'undefined') {
            bootstrap.Tooltip.getInstance($groupButton[0])?.hide();
        }

        if (focusTool) {
            let $items = this._getToolGroupItems($group);
            let $active = $items.filter('.leaflet-draw-toolbar-button-enabled');
            ($active.length > 0 ? $active : $items).first()[0]?.focus();
        }
    }

    /**
     * @private
     */
    _closeToolGroups() {
        $('.draw_tool_group_flyout').hide();
        $('.draw_tool_group_button').attr('aria-expanded', 'false');
    }

    /**
     * Marks the active tool, and the group it is in, as pressed.
     * @private
     */
    _refreshActiveTool() {
        let $container = $(this._mapControl.getContainer());

        $container.find('[data-draw-tool]').each(function (index, button) {
            button.setAttribute('aria-pressed', button.classList.contains('leaflet-draw-toolbar-button-enabled') ? 'true' : 'false');
        });

        $container.find('.draw_tool_group').each(function (index, group) {
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
            $button.attr({
                role: 'button',
                'aria-label': this._getToolAriaLabel(tool),
                'aria-pressed': $button.hasClass('leaflet-draw-toolbar-button-enabled') ? 'true' : 'false',
            }).css('background-image', 'none');
            let keyShortcuts = this._getToolAriaKeyShortcuts(tool);
            if (keyShortcuts !== '') {
                $button.attr('aria-keyshortcuts', keyShortcuts);
            }

            if (!tool.group) {
                $button.addClass('draw_icon').attr({
                    'data-bs-toggle': 'tooltip',
                    'data-bs-placement': 'right',
                    'data-bs-html': 'true',
                    'data-bs-title': this._getToolTooltipHtml(tool),
                }).html(
                    this._getButtonHtml(
                        tool.icon,
                        label,
                        this._getToolKeycap(tool),
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

        // The buttons are links; role=button promises Space activates them as well
        $buttonContainer.off('keydown.drawtoolspace').on('keydown.drawtoolspace', '[role="button"]', function (keyEvent) {
            if (keyEvent.key === ' ' && !keyEvent.ctrlKey && !keyEvent.altKey && !keyEvent.metaKey) {
                keyEvent.preventDefault();
                this.click();
            }
        });

        // An open flyout is positioned against its group button, so it would detach from it
        $container.closest('.route_manipulation_tools').off('.drawtoolgroups').on('scroll.drawtoolgroups', function () {
            self._closeToolGroups();
        });
        $(window).off('.drawtoolgroups').on('resize.drawtoolgroups', function () {
            self._closeToolGroups();
        });

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
        this._tools = tools;

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
        $(window).off('.drawtoolgroups');
        $('.route_manipulation_tools').off('.drawtoolgroups');

        // this.map.leafletMap.off(L.Draw.Event.CREATED);
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {DrawControls};
}
