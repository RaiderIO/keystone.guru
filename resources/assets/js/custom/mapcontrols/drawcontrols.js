$(function () {
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
                enabled: this.options.enemypatrol,
                handler: new L.Draw.EnemyPatrol(map, this.options.enemypatrol),
                title: this.options.enemypatrol.title
            }, {
                enabled: this.options.dungeonfloorswitchmarker,
                handler: new L.Draw.DungeonFloorSwitchMarker(map, this.options.dungeonfloorswitchmarker),
                title: this.options.dungeonfloorswitchmarker.title
            }
        ];
    };

    // Add some new strings to the draw controls
    // https://github.com/Leaflet/Leaflet.draw#customizing-language-and-text-in-leafletdraw
    $.extend(L.drawLocal.draw.handlers, {
        route: {
            tooltip: {
                start: 'Click to start drawing path.',
                cont: 'Click to continue drawing path.',
                end: 'Click the \'Finish\' button on the toolbar to complete your path.'
            }
        },
        brushline: {
            tooltip: {
                start: 'Click to start drawing line.',
                cont: 'Click and drag to continue drawing line.',
                end: 'Continue clicking/dragging, when done, press the \'Finish\' button on the toolbar to complete your line.'
            }
        }
    });
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

        // Add a created item to the list of drawn items
        this.map.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            let layer = event.layer;
            self.editableItemsLayer.addLayer(layer);
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

        this._attachHotkeys();

        // Remove delete all button -> https://stackoverflow.com/a/46949925
        L.EditToolbar.Delete.include({
            removeAllLayers: false
        });
    }

    _getHotkeys() {
        return [{
            hotkey: '1',
            cssClass: 'leaflet-draw-draw-path',
        }, {
            hotkey: '2',
            cssClass: 'leaflet-draw-draw-mapicon',
        }, {
            hotkey: '3',
            cssClass: 'leaflet-draw-draw-brushline',
        }, {
            hotkey: '4',
            cssClass: 'leaflet-draw-edit-edit',
        }];
    }

    _findHotkeyByCssClass(cssClass) {
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
        let hotkeys = this._getHotkeys();

        for (let index in hotkeys) {
            if (hotkeys.hasOwnProperty(index)) {
                let hotkey = hotkeys[index];
                this.map.hotkeys.attach(hotkey.hotkey, hotkey.cssClass);
            }
        }
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
                    title: 'Draw a path',
                    hotkey: this._findHotkeyByCssClass('path')
                },
                killzone: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    cssClass: 'd-none',
                    faClass: 'fa-bullseye',
                    title: 'Draw a killzone'
                },
                mapicon: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-icons',
                    title: 'Create an icon',
                    hotkey: this._findHotkeyByCssClass('icon')
                },
                awakenedobeliskgatewaymapicon: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    cssClass: 'd-none',
                    faClass: 'fa-icons',
                    title: 'Create an awakened obelisk gateway icon',
                },
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
            },
            edit: {
                featureGroup: this.editableItemsLayer, //REQUIRED!!
                remove: true
            }
        }
    }

    /**
     * Get HTML that should be placed inside a button that is used for interaction with the route.
     * @param faIconClass string
     * @param text string
     * @param hotkey string
     * @returns {string}
     * @private
     */
    _getButtonHtml(faIconClass, text, hotkey = '') {
        let template = Handlebars.templates['map_controls_route_edit_button_template'];

        let data = {
            fa_class: faIconClass,
            text: text,
            hotkey: hotkey
        };

        return template(data);
    }

    _addControlSetupBottomBar() {
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
            let $parent = $child.removeClass().addClass('row no-gutters');

            // Add columns to the buttons
            let $buttons = $parent.find('a');
            $buttons.addClass('col-lg col-3 draw_icon mt-2');
            $buttons.attr('data-toggle', 'tooltip');

            // The buttons have a parent that shouldn't be there; strip the children from that bad parent!
            $parent.append($buttons);
        });

        // Put the draw actions in a different div
        let $drawActions = $container.find('.leaflet-draw-actions');
        // Add the col class to make it align properly in its 'row' parent
        $drawActions.addClass('col');
        // Add to the proper container
        $('#edit_route_draw_actions_container').append(
            $drawActions
        )
    }

    _addControlSetupBrushlineButton() {
        let self = this;

        let $container = $(this._mapControl.getContainer());
        let $buttonContainer = $($container.children()[0]);

        // Add a special button for the Brushline
        let $brushlineButton = $('<a>', {
            class: 'leaflet-draw-draw-brushline col draw_icon mt-2' +
                // If pather was enabled, make sure it stays active
                (self.map.getMapState() instanceof PatherMapState ? ' leaflet-draw-toolbar-button-enabled' : ''),
            href: '#',
            'data-toggle': 'tooltip',
            title: lang.get('messages.brushline_title'),
        });
        $brushlineButton.html(
            this._getButtonHtml('fa-paint-brush', lang.get('messages.brushline'), this._findHotkeyByCssClass('brushline'))
        );
        $brushlineButton.bind('click', function (clickEvent) {
            // Check if it's enabled now
            let wasEnabled = self.map.getMapState() instanceof PatherMapState;
            // Enable it now
            if (!wasEnabled) {
                self.map.togglePather(true);
            }

            // Check if we were drawing anything else at this point, otherwise click the cancel button
            let $mainDrawActions = $('.leaflet-draw-actions:not(.leaflet-draw-actions-pather):visible');
            // Physically click the button
            let $a = $mainDrawActions.find('a');
            if ($a.length > 0) {
                // Cancel is always the last button
                $a.last()[0].click();
            }
        });
        $buttonContainer.append($brushlineButton);


        // Cancel button container
        let $drawActions = $('<ul>', {
            class: 'leaflet-draw-actions-pather leaflet-draw-actions leaflet-draw-actions-bottom col',
            style: 'top: 7px;'
        });
        // Add as the first child
        let $drawActionsContainer = $('#edit_route_draw_actions_container');
        $drawActionsContainer.find('.leaflet-draw-actions-pather').remove();
        $drawActionsContainer.append($drawActions);
        // Remove all previous entries
        $drawActions.empty();
        // Create the button
        let $button = $('<a>', {
            href: '#',
            'data-toggle': 'tooltip',
            title: lang.get('messages.finish_drawing'),
            text: lang.get('messages.finish')
        });

        // On click, disable pather
        $button.bind('click', function () {
            self.map.togglePather(false);
        });

        // Build the draw actions
        $drawActions.append($('<li>').append($button));

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
        $buttons.attr('data-toggle', 'tooltip');
        $($buttons[0]).html(this._getButtonHtml('fa-edit', lang.get('messages.edit'), this._findHotkeyByCssClass('edit')));
        $($buttons[1]).html(this._getButtonHtml('fa-trash', lang.get('messages.delete')));

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
                    let $option = $('.leaflet-draw-draw-' + optionName)
                        .html(this._getButtonHtml(option.faClass, lang.get('messages.' + optionName), option.hotkey))
                        .css('background-image', 'none');

                    // Add any css class that may or may not have been set
                    if (typeof option.cssClass !== 'undefined') {
                        $option.addClass(option.cssClass);
                    }
                }
            }
        }

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

        this.map.unregister('map:pathertoggled', this);
        // this.map.leafletMap.off(L.Draw.Event.CREATED);
    }
}