class DungeonMap extends Signalable {

    constructor(mapid, dungeonData, floorID, edit) {
        super();
        let self = this;

        this.dungeonData = dungeonData;

        this.mapObjectGroups = this._createMapObjectGroups();

        // Keep track of all objects that are added to the groups through whatever means; put them in the mapObjects array
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            this.mapObjectGroups[i].register('object:add', function (event) {
                self.mapObjects.push(event.data.object);
            });
        }

        /**
         * @var Array Stores all possible objects that are displayed on the map
         **/
        this.mapObjects = [];

        this.currentFloorId = floorID;
        this.edit = edit;

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
            new DungeonStartMarkerMapObjectGroup(this, 'dungeonstartmarker', 'DungeonStartMarker', false),
            new DungeonFloorSwitchMarkerMapObjectGroup(this, 'dungeonfloorswitchmarker', 'DungeonFloorSwitchMarker', false),
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
        if (this.edit) {
            this.drawControls = this._getDrawControls(this.drawnItems);
            this.drawControls.addControl();
        }

        // If we created something
        this.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            console.log(event);
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
            e.layers.eachLayer(function(i, layer){
                console.log(i, layer);
                let mapObject = self.findMapObjectByLayer(layer);
                console.log(mapObject);
            });
            console.log(L.Draw.Event.EDITED, e);
        });

        this.signal('map:refresh', {dungeonmap: this});

        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            this.mapObjectGroups[i].fetchFromServer(this.getCurrentFloor());
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