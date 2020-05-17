class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);
        this.sidebar = new Sidebar(options);

        this._colorPickers = [];
        this._currentlyActiveColorPicker = null;

        this._newPullKillZone = null;
    }

    /**
     * Called when the 'new pull' button has been pressed
     * @private
     */
    _newPull() {
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        this._newPullKillZone = killZoneMapObjectGroup.createNewPull();
    }

    /**
     * Selects a killzone based on a killzone, instead of a button click.
     * @param killZone
     * @private
     */
    _selectKillZoneByMapObject(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        this._killZoneRowClicked.call($(`#map_killzonessidebar_killzone_${killZone.id} .selectable`));
    }

    /**
     * Triggered whenever the user has selected a killzone
     * @param killZone {KillZone}
     * @param selected {Boolean}
     * @private
     */
    _killZoneSelected(killZone, selected) {
        // Deselect everything
        let classes = 'selected bg-success';
        $('#killzones_container .selected').removeClass(classes);

        // Select the new one if we should
        if (selected) {
            $(`#map_killzonessidebar_killzone_${killZone.id} .selectable`).addClass(classes);
        }
    }

    /**
     * Called when someone clicked on a killzone row and wants to switch selections accordingly
     * @private
     */
    _killZoneRowClicked(clickEvent) {
        // If there was an event, prevent clicking the 'expand' button also selecting the killzone
        if (clickEvent !== null && typeof clickEvent !== 'undefined' &&
            ($(clickEvent.target).hasClass('btn') || $(clickEvent.target).hasClass('fa'))) {
            return;
        }

        let map = getState().getDungeonMap();
        // Get the currently selected killzone ID, if any (so we may deselect it)
        let currentlySelectedKillZoneId = 0;
        let currentMapState = map.getMapState();
        if (currentMapState !== null && currentMapState instanceof EnemySelection) {
            currentlySelectedKillZoneId = currentMapState.getMapObject().id;
        }

        // Get the ID of the killzone that the user wants to select (or deselect)
        let selectedKillZoneId = parseInt($(this).closest('.map_killzonessidebar_killzone').data('id'));
        if (selectedKillZoneId === currentlySelectedKillZoneId) {
            // Selected what was already selected; select nothing instead
            selectedKillZoneId = 0;
        }

        // Find the killzone and if found, switch our map to a selection for that killzone
        let newMapState = null;
        if (selectedKillZoneId > 0) {
            let killZoneMapObjectGroup = map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            let killZone = killZoneMapObjectGroup.findMapObjectById(selectedKillZoneId);
            if (killZone !== null) {
                // Same as this.options.edit, really
                if (map.options.edit) {
                    newMapState = new KillZoneEnemySelection(map, killZone);
                } else {
                    // Just highlight the pull when the user clicked a pull
                    newMapState = new ViewKillZoneEnemySelection(map, killZone);
                }

                // Center the map to this killzone
                if (killZone.floor_id === getState().getCurrentFloor().id) {
                    getState().getDungeonMap().leafletMap.setView(killZone.getLayerCenteroid(), getState().getMapZoomLevel())
                }
            }
        }

        // Either de-select, or add a new state to the map
        map.setMapState(newMapState);
    }

    /**
     * Initializes a color picker.
     * @param killZone
     * @returns {*}
     * @private
     */
    _initColorPicker(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        // Simple example, see optional options for more configuration.
        return Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
            el: `#map_killzonessidebar_killzone_${killZone.id}_color`,
            default: killZone.color
        })).on('save', (color, instance) => {
            // Apply the new color
            let newColor = '#' + color.toHEXA().join('');
            if (killZone.color !== newColor) {
                killZone.color = newColor;
                killZone.save();
            }

            // Reset ourselves
            instance.hide();
        });
    }

    /**
     * Adds a killzone to the sidebar.
     * @param killZone
     * @private
     */
    _addKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        let self = this;

        let template = Handlebars.templates[
            this.options.edit ? 'map_killzonessidebar_killzone_row_edit_template' : 'map_killzonessidebar_killzone_row_view_template'
            ];

        let data = $.extend({
            'id': killZone.id,
            'text-class': 'text-white',
            'color': killZone.color // For viewing
        }, getHandlebarsDefaultVariables());

        $(this.options.killZonesContainerSelector).append(
            $(template(data))
        );

        $(`#map_killzonessidebar_killzone_${killZone.id}`).data('index', $($(this.options.killZonesContainerSelector).children()).length);
        $(`#map_killzonessidebar_killzone_${killZone.id} .selectable`).bind('click', this._killZoneRowClicked);

        if (this.options.edit) {
            $(`#map_killzonessidebar_killzone_${killZone.id}_color`).bind('click', function () {
                // Only one at a time
                if (self._currentlyActiveColorPicker !== null) {
                    self._currentlyActiveColorPicker.hide();
                }

                // Show the new color picker
                self._currentlyActiveColorPicker = self._colorPickers[killZone.id];
                self._currentlyActiveColorPicker.show();
            });
            let $hasKillZone = $(`#map_killzonessidebar_killzone_${killZone.id}_has_killzone`).bind('click', function () {
                // Inject the selectable in the _selectKillZone call to simulate selecting the actual killzone
                self._selectKillZoneByMapObject(killZone);

                if (killZone.layer === null) {
                    // Start drawing a killzone
                    $('.leaflet-draw-draw-killzone')[0].click();
                } else {
                    // @TODO This entire piece of code is hacky, should be done differently eventually
                    getState().getDungeonMap().drawnLayers.removeLayer(killZone.layer);
                    getState().getDungeonMap().editableLayers.removeLayer(killZone.layer);

                    let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
                    // It's been removed; unset it
                    killZoneMapObjectGroup.setLayerToMapObject(null, killZone);

                    killZone.floor_id = getState().getCurrentFloor().id;
                    // Update its visuals
                    killZone.redrawConnectionsToEnemies();
                    killZone.save();
                }
            });
            // If we have a killzone layer
            if (killZone.isKillZoneVisible()) {
                // Was inactive (always starts inactive), is active now
                $hasKillZone.button('toggle');
            }
            $(`#map_killzonessidebar_killzone_${killZone.id}_delete`).bind('click', this._deleteKillZone);
            this._colorPickers[killZone.id] = this._initColorPicker(killZone);
            // Small hack to get it to look better
            $(`#map_killzonessidebar_killzone_${killZone.id} .pcr-button`).addClass('h-100 w-100');
        }

        // Set some additional properties
        this._refreshKillZone(killZone);

        // If this killzone was created as a result of clicking the 'new pull' button
        if (this._newPullKillZone instanceof KillZone) {
            this._selectKillZoneByMapObject(this._newPullKillZone);

            this._newPullKillZone = null;
        }
    }

    /**
     *
     * @private
     */
    _deleteKillZone() {
        let self = this;

        let selectedKillZoneId = parseInt($(this).closest('.map_killzonessidebar_killzone').data('id'));
        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        let killZone = killZoneMapObjectGroup.findMapObjectById(selectedKillZoneId);

        $(this).find('i').removeClass('fa-trash').addClass('fa fa-circle-notch fa-spin');

        killZone.register('object:deleted', '123123', function () {
            showSuccessNotification(lang.get('messages.object.deleted'));
            $('#map_killzonessidebar_killzone_' + killZone.id).remove();

            // Bit hacky?
            if (killZone.isKillZoneVisible()) {
                getState().getDungeonMap().drawnLayers.removeLayer(killZone.layer);
                getState().getDungeonMap().editableLayers.removeLayer(killZone.layer);
            }

            killZone.unregister('object:deleted', '123123');
            killZone.cleanup();
        });
        // Failed to delete
        killZone.register('synced', '123123', function () {
            if (!killZone.synced) {
                $(self).find('i').addClass('fa-trash').removeClass('fa fa-circle-notch fa-spin')
            }

            killZone.unregister('synced', '123123');
        });

        killZone.delete();
    }

    /**
     * Removes a killzone from the sidebar.
     * @param killZone
     * @private
     */
    _removeKillZone(killZone) {
        $('#map_killzonessidebar_killzone_' + killZone.id).remove();
    }

    /**
     * Should be called whenever something's changed in the killzone that warrants a UI update
     * @param killZone
     * @private
     */
    _refreshKillZone(killZone) {
        console.warn('refreshing killzone!', killZone.color);
        let enemyForcesPercent = (killZone.getEnemyForces() / this.map.getEnemyForcesRequired()) * 100;
        enemyForcesPercent = Math.floor(enemyForcesPercent * 100) / 100;

        let index = $(`#map_killzonessidebar_killzone_${killZone.id}`).data('index');
        $(`#map_killzonessidebar_killzone_${killZone.id}_title`)
            .text(`${index}: ${killZone.enemies.length} enemies (${killZone.getEnemyForces()})`);

        // Fill the enemy list
        let npcs = [];
        let enemies = getState().getEnemies();
        for (let i = 0; i < killZone.enemies.length; i++) {
            let enemyId = killZone.enemies[i];
            for (let j = 0; j < enemies.length; j++) {
                let enemy = enemies[j];
                // If enemy found and said enemy has an npc
                if (enemy.id === enemyId && enemy.npc !== null) {
                    // If not in our array, add it
                    if (!npcs.hasOwnProperty(enemy.npc.id)) {
                        npcs[enemy.npc.id] = {
                            'npc': enemy.npc,
                            'count': 0
                        };
                    }

                    npcs[enemy.npc.id].count++;
                }
            }
        }

        let $enemyList = $(`#map_killzonessidebar_killzone_${killZone.id}_enemy_list`);
        $enemyList.children().remove();
        for (let index in npcs) {
            if (npcs.hasOwnProperty(index)) {
                let obj = npcs[index];

                let template = Handlebars.templates['map_killzonessidebar_killzone_row_enemy_row_template'];

                let data = $.extend({
                    'enemy_forces': obj.count * obj.npc.enemy_forces,
                    'count': obj.count,
                    'name': obj.npc.name,
                    'dangerous': obj.npc.dangerous === 1
                }, getHandlebarsDefaultVariables());

                $enemyList.append($(template(data)));
            }
        }

        if (this.options.edit) {
            this._colorPickers[killZone.id].setColor(killZone.color);
        }
    }

    /**
     *
     */
    activate() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        this.sidebar.activate();

        this.map = getState().getDungeonMap();

        let self = this;

        // Setup new pull button

        $(this.options.newKillZoneSelector).bind('click', this._newPull.bind(this));

        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            // Update the UI based on the new map states
            let previousMapState = mapStateChangedEvent.data.previousMapState;
            if (previousMapState instanceof EnemySelection) {
                self._killZoneSelected(previousMapState.getMapObject(), false);
            }
            let newMapState = mapStateChangedEvent.data.newMapState;
            if (newMapState instanceof EnemySelection) {
                self._killZoneSelected(newMapState.getMapObject(), true);
            }
        });

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.register('object:add', this, function (killZoneAddedEvent) {
            let killZone = killZoneAddedEvent.data.object;
            // Add the killzone to our list
            self._addKillZone(killZone);
            // Listen to changes in the killzone
            killZone.register(['killzone:enemyadded', 'killzone:enemyremoved', 'synced'], self, function (killZoneChangedEvent) {
                self._refreshKillZone(killZoneChangedEvent.context);
            });
        });
        // If the killzone was deleted, get rid of our display too
        killZoneMapObjectGroup.register('object:deleted', this, function (killZoneDeletedEvent) {
            let killZone = killZoneDeletedEvent.data.object;
            // Add the killzone to our list
            self._removeKillZone(killZone);
            // Stop listening to changes in the killzone
            killZone.unregister(['killzone:enemyadded', 'killzone:enemyremoved', 'synced'], self);
        });
    }

    /**
     *
     */
    cleanup() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        this.map.unregister('map:mapstatechanged', this);
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.unregister('object:add', this);

        this.sidebar.cleanup();
    }
}