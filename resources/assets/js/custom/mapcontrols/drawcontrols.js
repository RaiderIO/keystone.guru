L.DrawToolbar.prototype.getModeHandlers = function (map) {

    return [
        {
            enabled: this.options.path,
            handler: new L.Draw.Path(map, this.options.path),
            title: this.options.path.title
        }, {
            enabled: this.options.killzone,
            handler: new L.Draw.KillZone(map, this.options.killzone),
            title: this.options.killzone.title
        }, {
            enabled: this.options.mapicon,
            handler: new L.Draw.MapIcon(map, this.options.mapicon),
            title: this.options.mapicon.title
        }, {
            enabled: this.options.awakenedobeliskgatewaymapicon,
            handler: new L.Draw.AwakenedObeliskGatewayMapIcon(map, this.options.awakenedobeliskgatewaymapicon),
            title: this.options.awakenedobeliskgatewaymapicon.title
        }, {
            enabled: this.options.brushline,
            handler: new L.Draw.Brushline(map, this.options.brushline),
            title: this.options.brushline.title
        }, {
            enabled: this.options.enemypack,
            handler: new L.Draw.EnemyPack(map, this.options.enemypack),
            title: this.options.enemypack.title
        }, {
            enabled: this.options.enemy,
            handler: new L.Draw.Enemy(map, this.options.enemy),
            title: this.options.enemy.title
        }, {
            enabled: this.options.pridefulenemy,
            handler: new L.Draw.PridefulEnemy(map, this.options.pridefulenemy),
            title: this.options.pridefulenemy.title
        }, {
            enabled: this.options.enemypatrol,
            handler: new L.Draw.EnemyPatrol(map, this.options.enemypatrol),
            title: this.options.enemypatrol.title
        }, {
            enabled: this.options.dungeonfloorswitchmarker,
            handler: new L.Draw.DungeonFloorSwitchMarker(map, this.options.dungeonfloorswitchmarker),
            title: this.options.dungeonfloorswitchmarker.title
        }, {
            enabled: this.options.usermouseposition,
            handler: new L.Draw.UserMousePosition(map, this.options.usermouseposition),
            title: this.options.usermouseposition.title
        }
    ];
};

// Add some new strings to the draw controls
// https://github.com/Leaflet/Leaflet.draw#customizing-language-and-text-in-leafletdraw
$.extend(L.drawLocal.draw.handlers, {
    route: {
        tooltip: {
            start: lang.get('messages.draw_handler_route_tooltip_start'),
            cont: lang.get('messages.draw_handler_route_tooltip_cont'),
            end: lang.get('messages.draw_handler_route_tooltip_end')
        }
    },
    brushline: {
        tooltip: {
            start: lang.get('messages.draw_handler_brushline_tooltip_start'),
            cont: lang.get('messages.draw_handler_brushline_tooltip_cont'),
            end: lang.get('messages.draw_handler_brushline_tooltip_end')
        }
    }
});

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
            let layer = event.layer;
            // Prideful enemies are replaced with a real enemy that was hidden on the map. This is easier for various
            // reasons.
            if (event.layerType !== 'pridefulenemy') {
                self.editableItemsLayer.addLayer(layer);
            }
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

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        enemyMapObjectGroup.register('pridefulenemy:assigned', this, this._refreshPridefulButtonText.bind(this));
        enemyMapObjectGroup.register('pridefulenemy:unassigned', this, this._refreshPridefulButtonText.bind(this));

        this._attachHotkeys();

        // Remove delete all button -> https://stackoverflow.com/a/46949925
        L.EditToolbar.Delete.include({
            removeAllLayers: false
        });
    }

    /**
     *
     * @returns {Object}
     * @private
     */
    _getHotkeys() {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);
        let self = this;

        let hotkeys = [{
            hotkey: '1',
            cssClass: 'leaflet-draw-draw-path',
        }, {
            hotkey: '2',
            cssClass: 'leaflet-draw-draw-mapicon',
        }, {
            hotkey: '3',
            cssClass: 'leaflet-draw-draw-brushline',
        }, {
            hotkey: '5',
            cssClass: 'leaflet-draw-edit-edit',
        }, {
            hotkey: '6',
            cssClass: 'leaflet-draw-edit-remove',
        }];

        // Only when the route has a prideful affix
        if (getState().getMapContext().hasAffix(AFFIX_PRIDEFUL)) {
            hotkeys.push({
                hotkey: '4',
                cssClass: 'leaflet-draw-draw-pridefulenemy',
                enabled: function () {
                    let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                    return c.map.pridefulenemy.isEnabled() && enemyMapObjectGroup.getAssignedPridefulEnemies() < c.map.pridefulenemy.max;
                }
            });
        }

        return hotkeys;
    }

    /**
     * @param cssClass {String}
     * @returns {null|String}
     * @private
     */
    _findHotkeyByCssClass(cssClass) {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);

        let result = null;

        let hotkeys = this._getHotkeys();
        for (let index in hotkeys) {
            if (hotkeys.hasOwnProperty(index)) {
                let hotkey = hotkeys[index];
                if (hotkey.cssClass.endsWith(cssClass)) {
                    result = hotkey.hotkey;
                    break;
                }
            }
        }

        return result;
    }

    /**
     *
     * @protected
     */
    _attachHotkeys() {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);

        let hotkeys = this._getHotkeys();

        for (let index in hotkeys) {
            if (hotkeys.hasOwnProperty(index)) {
                let hotkey = hotkeys[index];
                this.map.hotkeys.attach(hotkey.hotkey, hotkey.cssClass, hotkey.enabled);
            }
        }
    }

    /**
     *
     * @private
     */
    _refreshPridefulButtonText() {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        let assignedPridefulEnemies = enemyMapObjectGroup.getAssignedPridefulEnemies();
        let buttonText = `${lang.get(`messages.pridefulenemy`)} (${assignedPridefulEnemies}/${c.map.pridefulenemy.max})`;
        $('.leaflet-draw-draw-pridefulenemy .button-text').text(buttonText);

        let limitReached = assignedPridefulEnemies === c.map.pridefulenemy.max || !c.map.pridefulenemy.isEnabled();

        $('#disabled_pridefulenemy_button .button-text').text(buttonText);
        $('#disabled_pridefulenemy_button').toggle(limitReached);
        $('.leaflet-draw-draw-pridefulenemy').toggleClass('leaflet-disabled draw-control-disabled', limitReached).toggle(!limitReached);
    }

    /**
     * Gets the newly generated options for the drawing control.
     * @returns object
     * @protected
     */
    _getDrawControlOptions() {
        console.assert(this instanceof DrawControls, 'this was not a DrawControls', this);

        let color = $('#edit_route_freedraw_options_color').val();
        let weight = $('#edit_route_freedraw_options_weight').val();

        if (typeof color === 'undefined') {
            color = c.map.polyline.defaultColor();
        }

        if (typeof weight === 'undefined') {
            weight = c.map.polyline.defaultWeight;
        }

        return {
            position: 'topleft',
            // This now shows/hides the brushline icon
            brushline: true,
            draw: {
                path: {
                    shapeOptions: {
                        color: color,
                        weight: weight,
                        opacity: 1.0
                    },
                    zIndexOffset: 1000,
                    faClass: 'fa-route',
                    title: lang.get('messages.path_title'),
                    hotkey: this._findHotkeyByCssClass('path')
                },
                killzone: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    cssClass: 'd-none',
                    faClass: 'fa-bullseye'
                },
                mapicon: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-icons',
                    title: lang.get('messages.mapicon_title'),
                    hotkey: this._findHotkeyByCssClass('icon')
                },
                awakenedobeliskgatewaymapicon: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    cssClass: 'd-none',
                    faClass: 'fa-icons'
                },
                pridefulenemy: getState().getMapContext().hasAffix(AFFIX_PRIDEFUL) ? {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-user',
                    title: lang.get('messages.pridefulenemy_title'),
                    hotkey: this._findHotkeyByCssClass('pridefulenemy')
                } : false,
                brushline: false,
                // Brushlines are added in a custom way since I'm using Pather for this
                // brushline: {
                //     shapeOptions: {
                //         color: color,
                //         weight: weight,
                //         opacity: 1.0
                //     },
                //     zIndexOffset: 1000,
                //     faClass: 'fa-paint-brush',
                //     title: 'Draw a line using a brush'
                // },
                enemypack: false,
                enemypatrol: false,
                enemy: false,
                dungeonfloorswitchmarker: false,
                usermouseposition: false,
            },
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

    _addControlSetupFakePridefulButton() {
        let $disabledPridefulButton = $('<a>', {
            id: 'disabled_pridefulenemy_button',
            class: 'draw_icon leaflet-disabled draw-control-disabled',
            href: '#'
        });

        $disabledPridefulButton.html(
            this._getButtonHtml(
                'fa-user',
                lang.get('messages.brushline'),
                '',
                c.map.pridefulenemy.isEnabled() ? lang.get('messages.pridefulenemy_disabled_title') : lang.get('messages.pridefulenemy_disabled_no_shadowlands_title')
            )
        );

        $disabledPridefulButton.hide();
        $disabledPridefulButton.insertAfter('.leaflet-draw-draw-pridefulenemy');
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
            let $child = $(child);

            // Clear of classes, add a row
            let $parent = $child.removeClass();

            // Add columns to the buttons
            let $buttons = $parent.find('a');
            $buttons.addClass('draw_icon');

            $.each($buttons, function (index, button) {
                let $button = $(button);
                let $row = $($button.children()[0]);
                $row.attr('data-toggle', 'tooltip');
                $row.attr('data-placement', 'right');
                $row.attr('title', $button.attr('title'));
            });

            // The buttons have a parent that shouldn't be there; strip the children from that bad parent!
            $parent.append($buttons);
        });

        let $originalDrawActions = $container.find('.leaflet-draw-actions');

        this.map.leafletMap.off(L.Draw.Event.TOOLBAROPENED).on(L.Draw.Event.TOOLBAROPENED, function (e) {
            // Ensure that pather is disabled now
            self.map.togglePather(false);

            // Put the draw actions in a different div
            let $drawActions = $container.find('.leaflet-draw-actions');
            $originalDrawActions.removeClass('row no-gutters').addClass('row no-gutters')
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

    _addControlSetupBrushlineButton() {
        let self = this;

        let $container = $(this._mapControl.getContainer());

        // Add a special button for the Brushline
        let $brushlineButton = $('<a>', {
            class: 'leaflet-draw-draw-brushline draw_icon mt-2' +
                // If pather was enabled, make sure it stays active
                (self.map.getMapState() instanceof PatherMapState ? ' leaflet-draw-toolbar-button-enabled' : ''),
            href: '#',
        });
        $brushlineButton.html(
            this._getButtonHtml(
                'fa-paint-brush',
                lang.get('messages.brushline'),
                this._findHotkeyByCssClass('brushline'),
                lang.get('messages.brushline_title')
            )
        );
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
                class: 'leaflet-draw-actions-pather leaflet-draw-actions leaflet-draw-actions-bottom row no-gutters',
            });
            // Create the button
            let $button = $('<a>', {
                href: '#',
                class: 'd-inline-block w-100 h-100',
                'data-toggle': 'tooltip',
                'data-placement': 'right',
                title: lang.get('messages.finish_drawing'),
                text: lang.get('messages.finish')
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

        // Depends on whether the prideful button was added or not
        if (getState().getMapContext().hasAffix(AFFIX_PRIDEFUL)) {
            $brushlineButton.insertBefore('.leaflet-draw-draw-pridefulenemy');
        } else {
            $brushlineButton.insertAfter('.leaflet-draw-draw-mapicon');
        }

        // Re-set pather to the same enabled state so all events are fired and UI is put back in a proper state
        this.map.togglePather(this.map.getMapState() instanceof PatherMapState);
    }

    _addControlSetupEditDeleteButtons() {
        let $container = $(this._mapControl.getContainer());
        let $buttonContainer = $($container.children()[0]);
        let $editRouteControls = $($container.children()[1]);

        // Add some padding for the above custom controls
        $editRouteControls.css('height', '0');

        // Add custom content for the edit and remove buttons
        let $buttons = $editRouteControls.find('a');
        let $editButton = $($buttons[0]);
        $editButton.html(this._getButtonHtml('fa-edit', lang.get('messages.edit'), this._findHotkeyByCssClass('edit'), lang.get('messages.edit_title')));
        $editButton.attr('title', '');

        let $deleteButton = $($buttons[1]);
        $deleteButton.html(
            this._getButtonHtml('fa-trash', lang.get('messages.delete'), this._findHotkeyByCssClass('delete'), lang.get('messages.delete_title'), 'btn-danger')
        );
        $deleteButton.attr('title', '');

        // Remove from the second row, inject in the first row
        $buttonContainer.append($buttons);
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

        // Add the control to the map
        this.drawControlOptions = this._getDrawControlOptions(this.editableItemsLayer);
        this._mapControl = new L.Control.Draw(this.drawControlOptions);
        this.map.leafletMap.addControl(this._mapControl);

        // If the option wants, render it with a font-awesome icon instead.
        // Surely there must be a better way for this but whatever, this works..
        for (let optionName in this.drawControlOptions.draw) {
            if (this.drawControlOptions.draw.hasOwnProperty(optionName)) {
                let option = this.drawControlOptions.draw[optionName];
                if (option.hasOwnProperty('faClass')) {
                    // Set the FA icon and remove the background image that was initially there
                    let $option = $(`.leaflet-draw-draw-${optionName}`)
                        .html(this._getButtonHtml(option.faClass, lang.get(`messages.${optionName}`), option.hotkey))
                        .css('background-image', 'none');

                    // Add any css class that may or may not have been set
                    if (typeof option.cssClass !== 'undefined') {
                        $option.addClass(option.cssClass);
                    }
                }
            }
        }

        this._addControlSetupFakePridefulButton();

        // Update the prideful button text to show (x/y)
        this._refreshPridefulButtonText();

        // Add the leaflet draw control to the bottom bar
        this._addControlSetupBottomBar();

        // Setup the brushline button, it's a custom contraption
        if (this.drawControlOptions.brushline !== false) {
            this._addControlSetupBrushlineButton();
        }

        // Edit and delete buttons need to be moved to the same container as the other buttons
        this._addControlSetupEditDeleteButtons();

        // Now done by the dungeonmap at the end of refresh
        // refreshTooltips();
        // refreshSelectPickers();
    }

    cleanup() {
        super.cleanup();

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        enemyMapObjectGroup.unregister('pridefulenemy:assigned', this);
        enemyMapObjectGroup.unregister('pridefulenemy:unassigned', this);
        // this.map.leafletMap.off(L.Draw.Event.CREATED);
    }
}
