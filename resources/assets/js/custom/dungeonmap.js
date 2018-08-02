class DungeonMap extends Signalable {
    /*
    var leafletMap;
     */
    constructor(mapid, dungeonData, dungeonId, floorID) {
        super();
        let self = this;

        this.dungeonData = dungeonData;

        this.mapObjectGroups = this._createMapObjectGroups();

        console.log(this.mapObjectGroups);

        // Keep track of all objects that are added to the groups through whatever means; put them in the mapObjects array
        for (let i in this.mapObjectGroups) {
            console.log(this.mapObjectGroups[i]);
            this.mapObjectGroups[i].register('object:add', function (event) {
                self.mapObjects.push(event.data.object);
            });
        }

        /**
         * @var Array Stores all possible objects that are displayed on the map
         **/
        this.mapObjects = [];

        this.currentDungeonId = dungeonId;
        this.currentFloorId = floorID;

        this.mapTileLayer = null;
        this.mapControls = null;
        this.drawControls = null;

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
        });

        // Refresh the map; draw the layers on it
        this.refreshLeafletMap();

        this.leafletMap.on('zoomend', (this._adjustZoomForLayers).bind(this));
        this.leafletMap.on('layeradd', (this._adjustZoomForLayers).bind(this));
    }

    /**
     *
     * @returns {[]}
     * @protected
     */
    _createMapObjectGroups() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        return [
            new EnemyMapObjectGroup(this, 'enemy', 'Enemy'),
            new EnemyPackMapObjectGroup(this, 'enemypack', 'EnemyPack'),
            new RouteMapObjectGroup(this, 'route', 'Route'),
            new DungeonStartMarkerMapObjectGroup(this, 'dungeonstartmarker', 'DungeonStartMarker'),
        ];
    }

    /**
     * Fixes the border width for based on current zoom of the map
     * @private
     */
    _adjustZoomForLayers() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        for (let i = 0; i < this.mapObjects.length; i++) {
            let layer = this.mapObjects[i].layer;
            if(layer.hasOwnProperty('setStyle')){
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
     * Get a new instance of a DrawControls object which will be added to the map
     * @param drawnItemsLayer
     * @returns {DrawControls}
     * @protected
     */
    _getDrawControls(drawnItemsLayer) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        return new DrawControls(this, drawnItemsLayer);
    }

    /**
     * Retrieves a map object group by its name.
     * @param name
     * @returns {boolean}|{MapObjectGroup}
     */
    getMapObjectGroupByName(name) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let result = false;
        for (let i in this.mapObjectGroups) {
            if (this.mapObjectGroups[i].name === name) {
                result = this.mapObjectGroups[i];
            }
        }

        return result;
    }

    /**
     * Get the data of the currently selected dungeon.
     * @returns {boolean|Object}
     */
    getCurrentDungeon() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');
        return this.getDungeonDataById(this.currentDungeonId);
    }

    /**
     * Gets the data of the currently selected floor
     * @returns {boolean|Object}
     */
    getCurrentFloor() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');
        return this.getDungeonFloorDataById(this.currentDungeonId, this.currentFloorId);
    }

    /**
     * Gets all data for a dungeon by its ID.
     * @param id string The ID of the dungeon you want to retrieve.
     * @returns {boolean|Object} False if the object could not be found, or the object.
     */
    getDungeonDataById(id) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let result = false;
        $.each(this.dungeonData, function (index, value) {
            if (parseInt(value.id) === parseInt(id)) {
                result = value;
                return false;
            }
        });
        return result;
    }

    /**
     * Gets all data of a dungeon floor by the dungeonId and its floorId
     * @param dungeonId string The ID of the dungeon.
     * @param floorId string The ID of the floor.
     * @returns {boolean|Object} False if the object could not be found, or the object.
     */
    getDungeonFloorDataById(dungeonId, floorId) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let dungeon = this.getDungeonDataById(dungeonId);
        let result = false;
        // Found the dungeon?
        if (dungeon !== false) {
            // Iterate over the found floors
            $.each(dungeon.floors, function (index, value) {
                // Find the floor we're looking for
                if (parseInt(value.id) === parseInt(floorId)) {
                    result = value;
                    return false;
                }
            });
        }
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
     * Refreshes the leaflet map so
     */
    refreshLeafletMap() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');
        let self = this;

        if (this.mapTileLayer !== null) {
            this.leafletMap.removeLayer(this.mapTileLayer);
        }
        this.leafletMap.setView([-128, 192], 0);
        let southWest = this.leafletMap.unproject([0, 4096], this.leafletMap.getMaxZoom());
        let northEast = this.leafletMap.unproject([6144, 0], this.leafletMap.getMaxZoom());


        this.mapTileLayer = L.tileLayer('https://mpplnr.wofje.nl/images/tiles/' + this.getCurrentDungeon().key + '/' + this.getCurrentFloor().index + '/{z}/{x}_{y}.png', {
            maxZoom: 4,
            attribution: '',
            tileSize: L.point(384, 256),
            noWrap: true,
            continuousWorld: true,
            bounds: new L.LatLngBounds(southWest, northEast)
        }).addTo(this.leafletMap);

        this.routeLayerGroup = new L.LayerGroup();

        // Configure the controls (toggle display of enemies, groups etc.)
        if (this.mapControls !== null) {
            this.mapControls.cleanup();
        }

        // Get the map controls and add it to the map
        this.mapControls = new MapObjectGroupControls(this);
        this.mapControls.addControl();

        // Configure the Draw Control (draw routes, enemies, enemy groups etc)
        // Make sure it does not get added multiple times
        if (this.drawControls !== null) {
            this.drawControls.cleanup();
        }

        // Refresh the list of drawn items
        this.drawnItems = new L.FeatureGroup();
        this.leafletMap.addLayer(this.drawnItems);

        // Get the draw controls and add it to the map
        this.drawControls = this._getDrawControls(this.drawnItems);
        this.drawControls.addControl();

        // If we created something
        this.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            console.log(event);
            let mapObjectGroup = self.getMapObjectGroupByName(event.layerType);
            if( mapObjectGroup !== false ){
                mapObjectGroup.createNew(event.layer);
            } else {
                console.warn('Unable to find MapObjectGroup after creating a ' + event.layerType);
            }
        });

        this.signal('map:refresh', {dungeonmap: this});

        //
        for (let i in this.mapObjectGroups) {
            this.mapObjectGroups[i].fetchFromServer(this.getCurrentFloor());
        }
    }

    /**
     * Removes an enemy pack from the leaflet map and our internal collection.
     * @param pack EnemyPack The pack to remove.
     */
    removeEnemyPack(pack) {
        console.assert(pack instanceof EnemyPack, pack, 'this is not an EnemyPack');

        // Remove the pack from the map
        this.leafletMap.removeLayer(pack.layer);
        // Remove it from our records
        let newEnemyPacks = [];
        for (let i = 0; i < this.enemyPacks.length; i++) {
            let packCandidate = this.enemyPacks[i];
            if (packCandidate.id !== pack.id) {
                newEnemyPacks.push(packCandidate);
            }
        }
        this.enemyPacks = newEnemyPacks;
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