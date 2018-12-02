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

        // How many map objects have returned a success status
        this.hotkeys = this._getHotkeys();
        this.mapObjectGroups = this._createMapObjectGroups();
        //  Whatever killzone is currently in select mode
        this.currentSelectModeKillZone = null;

        // Keep track of all objects that are added to the groups through whatever means; put them in the mapObjects array
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            this.mapObjectGroups[i].register('object:add', this, function (event) {
                let object = event.data.object;
                self.mapObjects.push(object);

                // Make sure we know it's editable
                if (object.isEditable() && event.data.objectgroup.editable && self.edit) {
                    self.drawnItems.addLayer(object.layer);
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
                layersLength++;
            });
        });

        this.leafletMap.on(L.Draw.Event.TOOLBAROPENED, function (e) {
            self.toolbarActive = true;
            // If a killzone was selected, unselect it now
            self.setSelectModeKillZone(null);
        });
        this.leafletMap.on(L.Draw.Event.TOOLBARCLOSED, function (e) {
            self.toolbarActive = false;
        });
        this.leafletMap.on(L.Draw.Event.DELETESTART, function (e) {
            self.deleteModeActive = true;
        });
        this.leafletMap.on(L.Draw.Event.DELETESTOP, function (e) {
            self.deleteModeActive = false;
        });

        this.leafletMap.on(L.Draw.Event.EDITSTART, function (e) {
            self.deleteModeActive = true;
        });
        this.leafletMap.on(L.Draw.Event.EDITSTOP, function (e) {
            self.deleteModeActive = false;
        });

        // If we created something
        this.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            let mapObjectGroup = self.getMapObjectGroupByName(event.layerType);
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
            this.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
                console.log(L.Draw.Event.DRAWSTART, e);
            });

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

    _getHotkeys() {
        return new Hotkeys(this);
    }

    /**
     * https://stackoverflow.com/questions/33614912/how-to-locate-leaflet-zoom-control-in-a-desired-position/33621034
     * @private
     */
    _createAdditionalControlPlaceholders() {
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
    _createMapObjectGroups() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let result = [];

        if (this.hiddenMapObjectGroups.indexOf('enemy') < 0) {
            result.push(new EnemyMapObjectGroup(this, 'enemy', 'Enemy', false));
        }
        if (this.hiddenMapObjectGroups.indexOf('enemypatrol') < 0) {
            result.push(new EnemyPatrolMapObjectGroup(this, 'enemypatrol', 'EnemyPatrol', false));
        }
        if (this.hiddenMapObjectGroups.indexOf('enemypack') < 0) {
            result.push(new EnemyPackMapObjectGroup(this, 'enemypack', 'EnemyPack', false));
        }

        // Only add these two if they're worth fetching (not in a view + no route (infested voting))
        if (this.getDungeonRoute().publicKey !== '' || this.edit) {
            if (this.hiddenMapObjectGroups.indexOf('route') < 0) {
                result.push(new RouteMapObjectGroup(this, 'route', true));
            }
            if (this.hiddenMapObjectGroups.indexOf('killzone') < 0) {
                result.push(new KillZoneMapObjectGroup(this, 'killzone', true));
            }
        }

        if (this.hiddenMapObjectGroups.indexOf('mapcomment') < 0) {
            result.push(new MapCommentMapObjectGroup(this, 'mapcomment', true));
        }
        if (this.hiddenMapObjectGroups.indexOf('dungeonstartmarker') < 0) {
            result.push(new DungeonStartMarkerMapObjectGroup(this, 'dungeonstartmarker', 'DungeonStartMarker', false));
        }
        if (this.hiddenMapObjectGroups.indexOf('dungeonfloorswitchmarker') < 0) {
            result.push(new DungeonFloorSwitchMarkerMapObjectGroup(this, 'dungeonfloorswitchmarker', 'DungeonFloorSwitchMarker', false));
        }

        return result;
    }

    /**
     * Create instances of all controls that will be added to the map (UI on the map itself)
     * @param drawnItemsLayer
     * @returns {*[]}
     * @private
     */
    _createMapControls(drawnItemsLayer) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let result = [];

        // No UI = no map controls at all
        if (!this.noUI) {
            if (this.edit) {
                result.push(new DrawControls(this, drawnItemsLayer));
            }

            // Only when enemy forces are relevant in their display (not in a view + no route (infested voting))
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
     * Retrieves a map object group by its name.
     * @param name
     * @returns {boolean}|{MapObjectGroup}
     */
    getMapObjectGroupByName(name) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let result = false;
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            if (this.mapObjectGroups[i].name === name) {
                result = this.mapObjectGroups[i];
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

        // If a killzone was selected, unselect it now
        this.setSelectModeKillZone(null);
        this.mapObjectGroupFetchSuccessCount = 0;

        if (this.mapTileLayer !== null) {
            this.leafletMap.removeLayer(this.mapTileLayer);
        }
        this.leafletMap.setView([-128, 192], 2);
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

        this.drawnItems = new L.FeatureGroup();
        this.mapControls = this._createMapControls(this.drawnItems);

        // Add new controls
        for (let i = 0; i < this.mapControls.length; i++) {
            this.mapControls[i].addControl();
        }

        // Refresh the list of drawn items
        this.leafletMap.addLayer(this.drawnItems);

        // If we confirmed editing something..
        this.signal('map:refresh', {dungeonmap: this});

        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            this.mapObjectGroups[i].fetchFromServer(this.getCurrentFloor(), this.mapObjectGroupFetchSuccess.bind(this));
        }
    }

    /**
     * Called whenever a map object group has claimed success over their AJAX request.
     * Once all map object groups have returned, this will fire an event that the data is ready to use in the map.
     */
    mapObjectGroupFetchSuccess() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        this.mapObjectGroupFetchSuccessCount++;
        // Let everyone know we're done and you can use all fetched data
        if (this.mapObjectGroupFetchSuccessCount === this.mapObjectGroups.length) {
            this.signal('map:mapobjectgroupsfetchsuccess');
        }
    }

    /**
     * Gets if there is currently a killzone in 'select mode'.
     * @returns {boolean}
     */
    isKillZoneSelectModeEnabled() {
        return this.currentSelectModeKillZone !== null;
    }

    /**
     * Sets the killzone that is currently in 'select mode'
     * @param killzone
     */
    setSelectModeKillZone(killzone = null) {
        let changed = this.currentSelectModeKillZone !== killzone;
        let previousKillzone = this.currentSelectModeKillZone;
        this.currentSelectModeKillZone = killzone;
        if (changed) {
            this.signal('map:killzoneselectmodechanged', {previousKillzone: previousKillzone, killzone: killzone});
        }
    }

    /**
     * Checks if try (hard) mode is currently enabled or not.
     * @returns {boolean|*}
     */
    isTryModeEnabled() {
        return this.getDungeonRoute().publicKey === '' && this.edit;
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