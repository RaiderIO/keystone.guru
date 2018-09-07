class DungeonMap extends Signalable {

    constructor(mapid, dungeonData, floorID, edit, teeming) {
        super();
        let self = this;

        this.dungeonData = dungeonData;

        // How many map objects have returned a success status
        this.mapObjectGroupFetchSuccessCount = 0;
        this.hotkeys = this._getHotkeys();
        this.mapObjectGroups = this._createMapObjectGroups();

        // Keep track of all objects that are added to the groups through whatever means; put them in the mapObjects array
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            this.mapObjectGroups[i].register('object:add', this, function (event) {
                let object = event.data.object;
                self.mapObjects.push(object);

                // Make sure we know it's editable
                if (event.data.objectgroup.editable && self.edit) {
                    self.drawnItems.addLayer(object.layer);
                }
            });
        }

        /** @var Array Stores all possible objects that are displayed on the map */
        this.mapObjects = [];
        /** @var Array Stores all UI elements that are drawn on the map */
        this.mapControls = [];

        this.currentFloorId = floorID;
        this.edit = edit;
        this.teeming = teeming;

        this.mapTileLayer = null;

        // Create the map object
        this.leafletMap = L.map(mapid, {
            center: [0, 0],
            minZoom: 1,
            maxZoom: 4,
            // We use a custom draw control, so don't use this
            // drawControl: true,
            // Simple 1:1 coordinates to meters, don't use Mercator or anything like that
            crs: L.CRS.Simple,
            // Context menu when right clicking stuff
            contextmenu: true,
            zoomControl: true
        });  //disable default scroll
        this.leafletMap.scrollWheelZoom.disable();

        // Make sure only control + scroll allows a zoom
        $("#map").bind('mousewheel DOMMouseScroll', function (event) {
            event.stopPropagation();
            if (event.ctrlKey === true) {
                event.preventDefault();
                self.leafletMap.scrollWheelZoom.enable();
                $('#map').removeClass('map-scroll');
                setTimeout(function () {
                    self.leafletMap.scrollWheelZoom.disable();
                }, 1000);
            } else {
                self.leafletMap.scrollWheelZoom.disable();
                $('#map').addClass('map-scroll');
            }
        });

        // Set all edited layers to no longer be synced.
        this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
            let layers = e.layers;
            layers.eachLayer(function (layer) {
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject');

                // No longer synched
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
            layers.eachLayer(function (layer) {
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject');

                if (typeof mapObject.delete === 'function') {
                    mapObject.delete();
                } else {
                    console.error(mapObject, ' does not have a delete() function!');
                }
            });
        });

        $(window).bind('mousedown', function (event) {
            $('#map').removeClass('map-scroll');
        });

        $(window).bind('mousewheel DOMMouseScroll', function (event) {
            $('#map').removeClass('map-scroll');
        });

        // Refresh the map; draw the layers on it
        this.refreshLeafletMap();

        this.leafletMap.on('zoomend', (this._adjustZoomForLayers).bind(this));
        this.leafletMap.on('layeradd', (this._adjustZoomForLayers).bind(this));
    }

    _getHotkeys() {
        return new Hotkeys(this);
    }

    /**
     *
     * @returns {[]}
     * @protected
     */
    _createMapObjectGroups() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        return [
            new EnemyMapObjectGroup(this, 'enemy', 'Enemy', false),
            new EnemyPatrolMapObjectGroup(this, 'enemypatrol', 'EnemyPatrol', false),
            new EnemyPackMapObjectGroup(this, 'enemypack', 'EnemyPack', false),
            new RouteMapObjectGroup(this, 'route', true),
            new KillZoneMapObjectGroup(this, 'killzone', true),
            new DungeonStartMarkerMapObjectGroup(this, 'dungeonstartmarker', 'DungeonStartMarker', false),
            new DungeonFloorSwitchMarkerMapObjectGroup(this, 'dungeonfloorswitchmarker', 'DungeonFloorSwitchMarker', false),
        ];
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
        if (this.edit) {
            result.push(new DrawControls(this, drawnItemsLayer));
        }

        result.push(new EnemyForcesControls(this));
        result.push(new MapObjectGroupControls(this));
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
            if (typeof popup !== 'undefined' && popup.isOpen()) {
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
        let self = this;

        if (this.mapTileLayer !== null) {
            this.leafletMap.removeLayer(this.mapTileLayer);
        }
        this.leafletMap.setView([-128, 192], 2);
        let southWest = this.leafletMap.unproject([0, 4096], this.leafletMap.getMaxZoom());
        let northEast = this.leafletMap.unproject([6144, 0], this.leafletMap.getMaxZoom());


        this.mapTileLayer = L.tileLayer('/images/tiles/' + this.dungeonData.expansion.shortname + '/' + this.dungeonData.key + '/' + this.getCurrentFloor().index + '/{z}/{x}_{y}.png', {
            maxZoom: 4,
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

        // If we confirmed editing something..
        this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
            e.layers.eachLayer(function (i, layer) {
                console.log(i, layer);
                let mapObject = self.findMapObjectByLayer(layer);
                console.log(mapObject);
            });
            console.log(L.Draw.Event.EDITED, e);
        });

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