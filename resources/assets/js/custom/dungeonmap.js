class DungeonMap extends Signalable {

    constructor(mapid, dungeonData, options) { // floorID, edit, teeming
        super();
        let self = this;

        this.dungeonData = dungeonData;

        this.options = options;

        // Keep track of whatever the current floor ID is
        this.currentFloorId = this.options.floorId;
        this.currentVisualType = this.options.defaultEnemyVisualType;

        // How many map objects have returned a success status
        this.hotkeys = this._getHotkeys();
        this.mapObjectGroupManager = new MapObjectGroupManager(this, this._getMapObjectGroupNames());
        this.mapObjectGroupManager.register('fetchsuccess', this, function () {
            // All layers have been fetched, refresh tooltips to update "No layers to edit" state
            refreshTooltips();

            self.signal('map:mapobjectgroupsfetchsuccess');
        });

        // The current enemy selection class in-use. Used for selecting enemies for whatever reason
        this.currentEnemySelection = null;
        //  Whatever killzone is currently in select mode
        this.currentSelectModeKillZone = null;
        // Pather instance
        this.pather = null;

        // Keep track of all objects that are added to the groups through whatever means; put them in the mapObjects array
        for (let i = 0; i < this.mapObjectGroupManager.mapObjectGroups.length; i++) {
            let mapObjectGroup = this.mapObjectGroupManager.mapObjectGroups[i];

            mapObjectGroup.register('object:add', this, function (addEvent) {
                let object = addEvent.data.object;
                self.mapObjects.push(object);
                self.drawnLayers.addLayer(object.layer);

                // Make sure we know it's editable
                if (object.isEditable() && addEvent.data.objectgroup.editable && self.options.edit) {
                    self.editableLayers.addLayer(object.layer);
                }
            });

            // Make sure we don't try to edit layers that aren't visible because they're hidden
            // If we don't do this and we have a hidden object, editing layers will break the moment you try to use it
            mapObjectGroup.register(['object:shown', 'object:hidden'], this, function (visibilityEvent) {
                let object = visibilityEvent.data.object;
                // If it's visible now and the layer is not added already
                if (visibilityEvent.data.visible && !self.drawnLayers.hasLayer(object.layer)) {
                    // Add it
                    self.drawnLayers.addLayer(object.layer);
                    // Only if we may add the layer
                    if (object.isEditable() && visibilityEvent.data.objectgroup.editable && self.options.edit) {
                        self.editableLayers.addLayer(object.layer);
                    }
                }
                // If it should not be visible but it's visible now
                else if (!visibilityEvent.data.visible && self.drawnLayers.hasLayer(object.layer)) {
                    // Remove it from the layer
                    self.drawnLayers.removeLayer(object.layer);
                    self.editableLayers.removeLayer(object.layer);
                }
            });
        }

        /** @var Array Stores all possible objects that are displayed on the map */
        this.mapObjects = [];
        /** @var Array Stores all UI elements that are drawn on the map */
        this.mapControls = [];
        // Keeps track of if we're in edit or delete mode
        this.toolbarActive = false;
        this.deleteModeActive = false;
        this.editModeActive = false;

        this.mapTileLayer = null;

        // Create the map object
        this.leafletMap = L.map(mapid, {
            center: [0, 0],
            minZoom: 1,
            maxZoom: 5,
            // We use a custom draw control, so don't use this
            // drawControl: true,
            // Simple 1:1 coordinates to meters, don't use Mercator or anything like that
            crs: L.CRS.Simple,
            // Context menu when right clicking stuff
            contextmenu: true,
            zoomControl: false
        });
        // Make sure we can place things in the center of the map
        this._createAdditionalControlPlaceholders();
        // Top left is reserved for the sidebar
        // this.leafletMap.zoomControl.setPosition('topright');

        // Special handling for brush drawing
        this.leafletMap.on(L.Draw.Event.DRAWSTART + ' ' + L.Draw.Event.EDITSTART + ' ' + L.Draw.Event.DELETESTART, function (e) {
            // Disable pather if we were doing it
            self.togglePather(false);
        });
        this.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
            // After adding, there may be layers when there were none. Fix the edit/delete tooltips
            refreshTooltips();
        });
        // Set all edited layers to no longer be synced.
        this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
            let layers = e.layers;
            layers.eachLayer(function (layer) {
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject');

                // No longer synced
                mapObject.setSynced(false);
                if (typeof mapObject.edit === 'function') {
                    mapObject.edit();
                } else {
                    console.error(mapObject, ' does not have an edit() function!');
                }
            });
        });

        this.leafletMap.on(L.Draw.Event.DELETED, function (e) {
            let layers = e.layers;
            let layersDeleted = 0;
            let layersLength = 0; // No default function for this

            let layerDeletedFn = function () {
                layersDeleted++;
                if (layersDeleted === layersLength) {
                    addFixedFooterSuccess(lang.get('messages.object.deleted'), 1500);
                }
            };

            layers.eachLayer(function (layer) {
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject');

                if (typeof mapObject.delete === 'function') {
                    mapObject.register('object:deleted', self, layerDeletedFn);
                    mapObject.delete();
                } else {
                    console.error(mapObject, ' does not have a delete() function!');
                }

                // Remove from both layers
                self.drawnLayers.removeLayer(layer);
                self.editableLayers.removeLayer(layer);

                layersLength++;
            });

            // After deleting, there may be no layers left. Fix the edit/delete tooltips
            refreshTooltips();
        });

        this.leafletMap.on(L.Draw.Event.TOOLBAROPENED, function (e) {
            self.toolbarActive = true;
            // If a killzone was selected, unselect it now
            if (self.isEnemySelectionEnabled()) {
                self.finishEnemySelection();
            }
        });
        this.leafletMap.on(L.Draw.Event.TOOLBARCLOSED, function (e) {
            self.toolbarActive = false;
        });
        this.leafletMap.on(L.Draw.Event.DELETESTART, function (e) {
            self.deleteModeActive = true;
            // Loop through each element to see if they are NOT editable, but ARE deleteable.
            // If so, we have to add them to the 'can delete this' list, and remove them after
            $.each(self.mapObjects, function (index, mapObject) {
                if (!mapObject.isEditable() && mapObject.isDeleteable()) {
                    self.editableLayers.addLayer(mapObject.layer);
                }
            });
        });
        this.leafletMap.on(L.Draw.Event.DELETESTOP, function (e) {
            self.deleteModeActive = false;

            // Now we make them un-editable again.
            $.each(self.mapObjects, function (index, mapObject) {
                if (!mapObject.isEditable() && mapObject.isDeleteable()) {
                    self.editableLayers.removeLayer(mapObject.layer);
                }
            });

            // Fix an issue where it'd remove all layers just because it got removed from the editable layers. Strange.
            self.leafletMap.removeLayer(self.drawnLayers);
            self.leafletMap.addLayer(self.drawnLayers);
        });

        this.leafletMap.on(L.Draw.Event.EDITSTART, function (e) {
            self.editModeActive = true;
        });
        this.leafletMap.on(L.Draw.Event.EDITSTOP, function (e) {
            self.editModeActive = false;
        });

        // If we created something
        this.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            // Find the corresponding map object group
            let mapObjectGroup = self.mapObjectGroupManager.getByName(event.layerType);
            if (mapObjectGroup !== false) {
                let object = mapObjectGroup.createNew(event.layer);
                // Save it to server instantly, manually saving is meh
                object.save();
            } else {
                console.warn('Unable to find MapObjectGroup after creating a ' + event.layerType);
            }
        });

        // Not very pretty but needed for debugging
        let verboseEvents = false;
        if (verboseEvents) {
            this.leafletMap.on('layeradd', function (e) {
                console.log('layeradd', e);
            });

            this.leafletMap.on(L.Draw.Event.CREATED, function (e) {
                console.log(L.Draw.Event.CREATED, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
                console.log(L.Draw.Event.EDITED, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETED, function (e) {
                console.log(L.Draw.Event.DELETED, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
                console.log(L.Draw.Event.DRAWSTART, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
                console.log(L.Draw.Event.DRAWSTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWVERTEX, function (e) {
                console.log(L.Draw.Event.DRAWVERTEX, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITSTART, function (e) {
                console.log(L.Draw.Event.EDITSTART, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITMOVE, function (e) {
                console.log(L.Draw.Event.EDITMOVE, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITRESIZE, function (e) {
                console.log(L.Draw.Event.EDITRESIZE, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITVERTEX, function (e) {
                console.log(L.Draw.Event.EDITVERTEX, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITSTOP, function (e) {
                console.log(L.Draw.Event.EDITSTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETESTART, function (e) {
                console.log(L.Draw.Event.DELETESTART, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETESTOP, function (e) {
                console.log(L.Draw.Event.DELETESTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.TOOLBAROPENED, function (e) {
                console.log(L.Draw.Event.TOOLBAROPENED, e);
            });
            this.leafletMap.on(L.Draw.Event.TOOLBARCLOSED, function (e) {
                console.log(L.Draw.Event.TOOLBARCLOSED, e);
            });
            this.leafletMap.on(L.Draw.Event.MARKERCONTEXT, function (e) {
                console.log(L.Draw.Event.MARKERCONTEXT, e);
            });
        }

        this.leafletMap.on('zoomend', (this._adjustZoomForLayers).bind(this));
        this.leafletMap.on('layeradd', (this._adjustZoomForLayers).bind(this));
    }

    /**
     * Set the map to be interactive or not. https://gis.stackexchange.com/a/54925
     * @param enabled
     * @private
     */
    _setMapInteraction(enabled) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        if (enabled) {
            this.leafletMap.dragging.enable();
            this.leafletMap.touchZoom.enable();
            this.leafletMap.doubleClickZoom.enable();
            this.leafletMap.scrollWheelZoom.enable();
            this.leafletMap.boxZoom.enable();
            this.leafletMap.keyboard.enable();
            if (this.leafletMap.tap) this.leafletMap.tap.enable();
            document.getElementById('map').style.cursor = 'grab';
        } else {
            this.leafletMap.dragging.disable();
            this.leafletMap.touchZoom.disable();
            this.leafletMap.doubleClickZoom.disable();
            this.leafletMap.scrollWheelZoom.disable();
            this.leafletMap.boxZoom.disable();
            this.leafletMap.keyboard.disable();
            if (this.leafletMap.tap) this.leafletMap.tap.disable();
            document.getElementById('map').style.cursor = 'default';
        }
    }

    /**
     * Get the current HotKeys object used for binding actions to hotkeys.
     * @returns {Hotkeys}
     * @private
     */
    _getHotkeys() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        return new Hotkeys(this);
    }

    /**
     * https://stackoverflow.com/questions/33614912/how-to-locate-leaflet-zoom-control-in-a-desired-position/33621034
     * @private
     */
    _createAdditionalControlPlaceholders() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let corners = this.leafletMap._controlCorners,
            l = 'leaflet-',
            container = this.leafletMap._controlContainer;

        function createCorner(vSide, hSide) {
            let className = l + vSide + ' ' + l + hSide;

            corners[vSide + hSide] = L.DomUtil.create('div', className, container);
        }

        createCorner('verticalcenter', 'left');
        createCorner('verticalcenter', 'right');

        createCorner('top', 'horizontalcenter');
        createCorner('bottom', 'horizontalcenter');
    }

    /**
     *
     * @returns {[]}
     * @protected
     */
    _getMapObjectGroupNames() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        // Remove the hidden groups from the list of available groups
        return _.difference(MAP_OBJECT_GROUP_NAMES, this.options.hiddenMapObjectGroups);
    }

    /**
     * Create instances of all controls that will be added to the map (UI on the map itself)
     * @param editableLayers
     * @returns {*[]}
     * @private
     */
    _createMapControls(editableLayers) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let result = [];

        // No UI = no map controls at all
        if (!this.options.noUI) {
            if (this.options.edit) {
                result.push(new DrawControls(this, editableLayers));
            }

            // Only when enemy forces are relevant in their display (not in a view)
            if (this.getDungeonRoute().publicKey !== '' || this.options.edit) {
                result.push(new EnemyForcesControls(this));
            }
            result.push(new EnemyVisualControls(this));
            result.push(new MapObjectGroupControls(this));

            if (this.isTryModeEnabled() && this.dungeonData.name === 'Siege of Boralus') {
                result.push(new FactionDisplayControls(this));
            }

            // result.push(new AdDisplayControls(this));
        }

        return result;
    }

    /**
     * Fixes the border width for based on current zoom of the map
     * @private
     */
    _adjustZoomForLayers() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        // @TODO Verify if this code still does what it's supposed to do?
        for (let i = 0; i < this.mapObjects.length; i++) {
            let layer = this.mapObjects[i].layer;
            if (layer.hasOwnProperty('setStyle')) {
                let zoomStep = Math.max(2, this.leafletMap.getZoom());
                if (layer instanceof L.Polyline) {
                    layer.setStyle({radius: 10 / Math.max(1, (this.leafletMap.getMaxZoom() - this.leafletMap.getZoom()))})
                } else if (layer instanceof L.CircleMarker) {
                    layer.setStyle({radius: 10 / Math.max(1, (this.leafletMap.getMaxZoom() - this.leafletMap.getZoom()))})
                } else {
                    layer.setStyle({weight: 3 / zoomStep});
                }
            }
        }
    }

    /**
     *
     * @returns {boolean}
     */
    hasPopupOpen() {
        let result = false;
        for (let i = 0; i < this.mapObjects.length; i++) {
            let mapObject = this.mapObjects[i];
            let popup = mapObject.layer.getPopup();
            if (typeof popup !== 'undefined' && popup !== null && popup.isOpen()) {
                result = true;
                break;
            }
        }
        return result;
    }

    /**
     * Finds a floor by id.
     * @param floorId
     * @returns {*}|bool
     */
    getFloorById(floorId) {
        let result = false;

        for (let i = 0; i < this.dungeonData.floors.length; i++) {
            let floor = this.dungeonData.floors[i];
            if (floor.id === floorId) {
                result = floor;
                break;
            }
        }

        return result;
    }

    /**
     * Gets the data of the currently selected floor
     * @returns {boolean|Object}
     */
    getCurrentFloor() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let self = this;
        let result = false;
        // Iterate over the found floors
        $.each(this.dungeonData.floors, function (index, value) {
            // Find the floor we're looking for
            if (parseInt(value.id) === parseInt(self.currentFloorId)) {
                result = value;
                return false;
            }
        });

        return result;
    }

    /**
     * Finds a map object by means of a Leaflet layer.
     * @param layer object The layer you want the map object for.
     */
    findMapObjectByLayer(layer) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let result = false;
        for (let i = 0; i < this.mapObjects.length; i++) {
            let mapObject = this.mapObjects[i];
            if (mapObject.layer === layer) {
                result = mapObject;
                break;
            }
        }
        return result;
    }

    /**
     * Get the amount of enemy forces that are required to complete this dungeon.
     * @returns {*}
     */
    getEnemyForcesRequired() {
        return this.teeming ? this.dungeonData.enemy_forces_required_teeming : this.dungeonData.enemy_forces_required;
    }

    /**
     * Refreshes the leaflet map so
     */
    refreshLeafletMap() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let self = this;

        this.signal('map:beforerefresh', {dungeonmap: this});

        // If we were selecting enemies, stop doing that now
        if (this.isEnemySelectionEnabled()) {
            this.finishEnemySelection();
        }

        if (this.mapTileLayer !== null) {
            this.leafletMap.removeLayer(this.mapTileLayer);
        }
        this.leafletMap.setView([-128, 192], this.options.defaultZoom);
        let southWest = this.leafletMap.unproject([0, 8192], this.leafletMap.getMaxZoom());
        let northEast = this.leafletMap.unproject([12288, 0], this.leafletMap.getMaxZoom());


        this.mapTileLayer = L.tileLayer('/images/tiles/' + this.dungeonData.expansion.shortname + '/' + this.dungeonData.key + '/' + this.getCurrentFloor().index + '/{z}/{x}_{y}.png', {
            maxZoom: 5,
            attribution: 'Map data © Blizzard Entertainment',
            tileSize: L.point(384, 256),
            noWrap: true,
            continuousWorld: true,
            bounds: new L.LatLngBounds(southWest, northEast)
        }).addTo(this.leafletMap);

        // Remove existing map controls
        for (let i = 0; i < this.mapControls.length; i++) {
            this.mapControls[i].cleanup();
        }

        this.editableLayers = new L.FeatureGroup();
        this.mapControls = this._createMapControls(this.editableLayers);

        // Add new controls
        for (let i = 0; i < this.mapControls.length; i++) {
            this.mapControls[i].addControl();
        }

        // Refresh the list of drawn items
        this.drawnLayers = new L.FeatureGroup();
        this.leafletMap.addLayer(this.drawnLayers);
        this.leafletMap.addLayer(this.editableLayers);

        // If we confirmed editing something..
        this.signal('map:refresh', {dungeonmap: this});

        // Show/hide the attribution
        if (!this.options.showAttribution) {
            $('.leaflet-control-attribution').hide();
        }

        // Pather for drawing lines
        if (this.pather !== null) {
            this.leafletMap.removeLayer(this.pather);
        }

        this.pather = new L.Pather();
        this.pather.on('created', function (patherEvent) {
            // Add the newly created polyline to our system
            let mapObjectGroup = self.mapObjectGroupManager.getByName('brushline');

            // Create a new brushline
            let points = [];

            // Convert the latlngs into something the polyline constructor understands
            let vertices = patherEvent.latLngs;
            for (let i = 0; i < vertices.length; i++) {
                let vertex = vertices[i];
                points.push([vertex.lat, vertex.lng]);
            }

            let layer = L.polyline(points);

            let object = mapObjectGroup.createNew(layer);
            object.save();

            // Remove it from Pather, we only use Pather for creating the actual layer
            self.pather.removePath(patherEvent.polyline);
        });
        this.leafletMap.addLayer(this.pather);
        // Set its options properly
        this.refreshPather();
        // Not enabled at this time
        this.togglePather(false);
    }

    /**
     * Gets if enemy selection is enabled or not.
     * @returns {boolean}
     */
    isEnemySelectionEnabled() {
        return this.currentEnemySelection !== null;
    }

    /**
     * Gets the current enemy selection instance.
     * @returns {null}
     */
    getEnemySelection() {
        return this.currentEnemySelection;
    }

    /**
     * Start the enemy selection
     * @param enemySelection The instance of an EnemySelection object which defines what may be selected.
     */
    startEnemySelection(enemySelection) {
        console.assert(enemySelection instanceof EnemySelection, this, 'enemySelection is not an EnemySelection');
        if (this.currentEnemySelection === null) {
            this.currentEnemySelection = enemySelection;
            this.signal('map:enemyselectionmodechanged', {enemySelection: this.currentEnemySelection, finished: false});
            this.currentEnemySelection.startSelectMode();
        } else {
            console.error('Unable to assign enemy selection when we\'re already selecting enemies!', this.currentEnemySelection, enemySelection);
        }
    }

    /**
     * Finishes the current enemy selection, if there's one going on at the moment.
     */
    finishEnemySelection() {
        if (this.currentEnemySelection !== null) {
            this.currentEnemySelection.cancelSelectMode();
            this.signal('map:enemyselectionmodechanged', {enemySelection: this.currentEnemySelection, finished: true});
            this.currentEnemySelection = null;
        } else {
            console.error('Unable to finish enemy selection; we\'re currently not selecting any enemies now');
        }
    }

    /**
     * Checks if try (hard) mode is currently enabled or not.
     * @returns {boolean|*}
     */
    isTryModeEnabled() {
        return this.options.try && this.options.edit;
    }

    /**
     * Get data related to the dungeon route we're displaying (may be a dummy/empty dungeon route)
     * @returns {*}
     */
    getDungeonRoute() {
        return this.options.dungeonroute;
    }

    /**
     * Sets the visual type that is currently being displayed.
     * @param visualType
     */
    setVisualType(visualType) {
        this.currentVisualType = visualType;
    }

    /**
     * Get the default visual to display for all enemies.
     * @returns {string}
     */
    getVisualType() {
        return this.currentVisualType;
    }

    /**
     * Checks if pather is currently active or not.
     * @returns {boolean}
     */
    isPatherActive() {
        return this.pather !== null && this.pather.getMode() === L.Pather.MODE.CREATE;
    }

    /**
     * Toggle pather to be enabled or not.
     * @param enabled
     */
    togglePather(enabled) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        // May be null when initializing
        if (this.pather !== null) {
            //  When enabled, add to the map
            if (enabled) {
                this.pather.setMode(L.Pather.MODE.CREATE);
            } else {
                this.pather.setMode(L.Pather.MODE.VIEW);
            }

            this.signal('map:pathertoggled', {enabled: enabled});
        }
    }

    /**
     *
     */
    refreshPather() {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);
        console.assert(this.pather instanceof L.Pather, 'this.pather is not a L.Pather', this.pather);
        this.pather.setOptions({
            strokeWidth: c.map.polyline.defaultWeight,
            smoothFactor: 5,
            pathColour: c.map.polyline.defaultColor
        });
    }
}


// let amount = 16;// 8192 / space
// for (let x = 0; x <= amount; x++) {
//     for (let y = 0; y <= amount; y++) {
//         L.marker(this.leafletMap.unproject([x * (6144 / amount), y * (4096 / amount)], this.leafletMap.getMaxZoom())).addTo(this.leafletMap);
//     }
// }

// L.marker(southWest).addTo(this.leafletMap);
// L.marker(northEast).addTo(this.leafletMap);

// var geoJsonTest = new L.geoJson(geojsonFeature, {
//     coordsToLatLng: function (newcoords) {
//         return (map.unproject([newcoords[1], newcoords[0]], map.getMaxZoom()));
//     },
//     pointToLayer: function (feature, coords) {
//         return L.circleMarker(coords, geojsonMarkerOptions);
//     }
// });