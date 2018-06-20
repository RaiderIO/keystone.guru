class DungeonMap {
    /*
    var leafletMap;
     */
    constructor(mapid, dungeonData, dungeonId, floorID) {
        let self = this;

        this.dungeonData = dungeonData;

        /**
         * @var Array Stores all enemy packs which are displayed on the map
         **/
        this.enemyPacks = [];
        this.enemyPackClassName = "EnemyPack";

        this.enemies = [];
        this.enemyClassName = "Enemy";

        /**
         * @var Array Stores all possible objects that are displayed on the map
         **/
        this.mapObjects = [];

        this.currentDungeonId = dungeonId;
        this.currentFloorId = floorID;

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
        });

        // Refresh the map; draw the layers on it
        this.refreshLeafletMap();


        this.leafletMap.on('zoomend', (this._adjustZoomForLayers).bind(this));
        // Playground
    }

    /**
     * Fixes the border width for based on current zoom of the map
     * @private
     */
    _adjustZoomForLayers() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        for (let i = 0; i < this.mapObjects.length; i++) {
            let layer = this.mapObjects[i].layer;
            layer.setStyle({weight: 3 / Math.max(2, this.leafletMap.getZoom())});
        }
    }

    /**
     * Factory for creating a new enemy pack.
     * @returns {EnemyPack}
     * @private
     */
    _createEnemyPack(layer) {
        switch (this.enemyPackClassName) {
            case "AdminEnemyPack":
                return new AdminEnemyPack(this, layer);
            default:
                return new EnemyPack(this, layer);
        }
    }

    /**
     * Factory for creating a new enemy.
     * @returns {Enemy}
     * @private
     */
    _createEnemy(layer) {
        switch (this.enemyClassName) {
            case "AdminEnemy":
                return new AdminEnemy(this, layer);
            default:
                return new Enemy(this, layer);
        }
    }

    /**
     * Get the data of the currently selected dungeon.
     * @returns {boolean|Object}
     */
    getCurrentDungeon() {
        return this.getDungeonDataById(this.currentDungeonId);
    }

    /**
     * Gets the data of the currently selected floor
     * @returns {boolean|Object}
     */
    getCurrentFloor() {
        return this.getDungeonFloorDataById(this.currentDungeonId, this.currentFloorId);
    }

    /**
     * Gets all data for a dungeon by its ID.
     * @param id string The ID of the dungeon you want to retrieve.
     * @returns {boolean|Object} False if the object could not be found, or the object.
     */
    getDungeonDataById(id) {
        let result = false;
        $.each(this.dungeonData, function (index, value) {
            if (value.id === id) {
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
        let dungeon = this.getDungeonDataById(dungeonId);
        let result = false;
        // Found the dungeon?
        if (dungeon !== false) {
            // Iterate over the found floors
            $.each(dungeon.floors, function (index, value) {
                // Find the floor we're looking for
                if (value.id === floorId) {
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


        // Refresh the packs on the map; re-add them
        this.refreshEnemyPacks();
    }

    /**
     * Refreshes the enemy packs that are displayed on the map based on the current dungeon & selected floor.
     */
    refreshEnemyPacks() {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let floor = this.getCurrentFloor();
        let self = this;

        $.ajax({
            type: 'GET',
            url: '/api/v1/enemypacks',
            dataType: 'json',
            data: {
                floor_id: floor.id
            },
            beforeSend: function () {
                console.log("beforeSend");
            },
            success: function (json) {
                console.log(json);

                // Remove any layers that were added before
                for (let i = 0; i < self.enemyPacks.length; i++) {
                    let enemyPack = self.enemyPacks[i];
                    // Remove all layers
                    self.leafletMap.removeLayer(enemyPack.layer);
                }

                // Now draw the packs on the map
                for (let i = 0; i < json.length; i++) {
                    let points = [];
                    let remoteEnemyPack = json[i];
                    for (let j = 0; j < remoteEnemyPack.vertices.length; j++) {
                        let vertex = remoteEnemyPack.vertices[j];
                        points.push([vertex.y, vertex.x]);
                    }

                    let layer = L.polygon(points);


                    let enemyPack = self.addEnemyPack(layer);
                    enemyPack.id = remoteEnemyPack.id;
                    // We just downloaded the enemy pack, it's synced alright!
                    enemyPack.setSynced(true);
                }

            },
            complete: function () {
                console.log("complete");
            }
        });
    }

    /**
     * Adds an enemy pack to the map and to the internal collection of packs.
     * @param layer The layer that represents the pack
     * @return EnemyPack
     */
    addEnemyPack(layer) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let enemyPack = this._createEnemyPack(layer);
        this.enemyPacks.push(enemyPack);
        this.mapObjects.push(enemyPack);
        layer.addTo(this.leafletMap);

        enemyPack.onLayerInit();

        return enemyPack;
    }

    /**
     * Adds an enemy to the map and to the internal collection of enemies.
     * @param layer The layer that represents the pack
     * @return EnemyPack
     */
    addEnemy(layer) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let enemy = this._createEnemy(layer);
        this.enemies.push(enemy);
        this.mapObjects.push(enemy);
        layer.addTo(this.leafletMap);

        enemy.onLayerInit();

        return enemy;
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


// Code for the statusbar
// L.Control.Statusbar = L.Control.extend({
//     onAdd: function (map) {
//         _statusbar = $("<p>")
//             .css('font-size', '20px')
//             .css('font-weight', 'bold')
//             .css('color', '#5DADE2')
//             .html('Test status bar');
//         _statusbar = _statusbar[0];
//
//         return _statusbar;
//     }
// });
//
// L.control.statusbar = function (opts) {
//     return new L.Control.Statusbar(opts);
// };
//
// L.control.statusbar({position: 'topright'}).addTo(mapObj);
// let amount = 16;// 8192 / space
// for (let x = 0; x <= amount; x++) {
//     for (let y = 0; y <= amount; y++) {
//         L.marker(this.leafletMap.unproject([x * ( 6144 / amount), y * (4096 / amount)], this.leafletMap.getMaxZoom())).addTo(this.leafletMap);
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