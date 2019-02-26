class DungeonMap extends Signalable {

    constructor(mapid, dungeonData, options) { // floorID, edit, teeming
        super();
        let self = this;

        this.dungeonData = dungeonData;

        this.options = options;
        this.currentFloorId = options.floorId;
        this.edit = options.edit;
        this.teeming = options.teeming;
        this.dungeonroute = options.dungeonroute;
        this.visualType = options.defaultEnemyVisualType;
        this.noUI = options.noUI;
        this.hiddenMapObjectGroups = options.hiddenMapObjectGroups;
        this.defaultZoom = options.defaultZoom;

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

        // Keep track of all objects that are added to the groups through whatever means; put them in the mapObjects array
        for (let i = 0; i < this.mapObjectGroupManager.mapObjectGroups.length; i++) {
            let mapObjectGroup = this.mapObjectGroupManager.mapObjectGroups[i];

            mapObjectGroup.register('object:add', this, function (addEvent) {
                let object = addEvent.data.object;
                self.mapObjects.push(object);
                self.drawnLayers.addLayer(object.layer);

                // Make sure we know it's editable
                if (object.isEditable() && addEvent.data.objectgroup.editable && self.edit) {
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
                    if (object.isEditable() && visibilityEvent.data.objectgroup.editable && self.edit) {
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
        this.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
            // If it's brushline, disable, otherwise enable
            if (e.layerType === 'brushline') {
                self._startBrushlineDrawing();
            }
        });
        this.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
            if (e.layerType === 'brushline') {
                self._stopBrushlineDrawing();
            }

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
                    // @TODO JS translations?
                    addFixedFooterSuccess("Objects deleted successfully.");
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
     * Should be called when the brushline drawing has started by the user.
     * @private
     */
    _startBrushlineDrawing() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');
        let self = this;

        this._setMapInteraction(false);

        let workingLayer = null;
        let lastMouseX, lastMouseY;

        // Fn for when a layer is first added after starting the brushline drawing.
        let layerAddFn = function (mouseEvent) {
            // Only if the layer is the made polyline!
            if (workingLayer === null && mouseEvent.layer.hasOwnProperty('_latlngs')) {
                workingLayer = mouseEvent.layer;
                // Unsub
                self.leafletMap.off('layeradd', layerAddFn);
            }
            // Initial click on the map
            // else {
            //     let el = document.getElementById('map');
            //     let ev = document.createEvent("MouseEvent");
            //
            //     ev.initMouseEvent(
            //         "click",
            //         true /* bubble */, true /* cancelable */,
            //         window, null,
            //         lastMouseX, lastMouseY, 0, 0, /* coordinates */
            //         false, false, false, false, /* modifier keys */
            //         0 /*left*/, null
            //     );
            //     el.dispatchEvent(ev);
            // }
        };
        this.leafletMap.on('layeradd', layerAddFn);

        this.leafletMap.on('mousemove', function (mouseEvent) {
            lastMouseX = mouseEvent.layerPoint.x;
            lastMouseY = mouseEvent.layerPoint.y;

            // Only when a button is pressed
            if (mouseEvent.originalEvent.buttons === 1 && workingLayer !== null) {
                let latLngs = workingLayer.getLatLngs();

                let mayAdd = true;
                if (latLngs.length > 0) {
                    let lastLatLng = latLngs[latLngs.length - 1];

                    if (getDistanceSquared(lastLatLng, mouseEvent.latlng) < c.map.brushline.minDrawDistanceSquared) {
                        mayAdd = false;
                    }
                }

                if (mayAdd) {
                    workingLayer.addLatLng(mouseEvent.latlng);
                }
            }
        });
    }

    /**
     * Should be called when the brushline drawing is stopped by the user.
     * @private
     */
    _stopBrushlineDrawing() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        this._setMapInteraction(true);

        this.leafletMap.off('mousemove');
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
        return _.difference(MAP_OBJECT_GROUP_NAMES, this.hiddenMapObjectGroups);
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
        if (!this.noUI) {
            if (this.edit) {
                result.push(new DrawControls(this, editableLayers));
            }

            // Only when enemy forces are relevant in their display (not in a view)
            if (this.getDungeonRoute().publicKey !== '' || this.edit) {
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

        this.signal('map:beforerefresh', {dungeonmap: this});

        // If we were selecting enemies, stop doing that now
        if (this.isEnemySelectionEnabled()) {
            this.finishEnemySelection();
        }

        if (this.mapTileLayer !== null) {
            this.leafletMap.removeLayer(this.mapTileLayer);
        }
        this.leafletMap.setView([-128, 192], this.defaultZoom);
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
        return this.getDungeonRoute().publicKey === 'try' && this.edit;
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
        this.visualType = visualType;
    }

    /**
     * Get the default visual to display for all enemies.
     * @returns {string}
     */
    getVisualType() {
        return this.visualType;
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