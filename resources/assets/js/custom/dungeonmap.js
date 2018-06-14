class DungeonMap {
    /*
    var leafletMap;
     */
    constructor(mapid, dungeonData, dungeonId, floorID) {

        this.dungeonData = dungeonData;

        /**
         * @var Object Stores all enemy packs which are displayed on the map
         **/
        this.enemyPacks = [];
        this.enemyPackClassName = "EnemyPack";

        this.currentDungeonId = dungeonId;
        this.currentFloorId = floorID;

        this.mapTileLayer = null;

        // Create the map object
        this.leafletMap = L.map(mapid, {
            minZoom: 1,
            maxZoom: 4,
            // We use a custom draw control, so don't use this
            // drawControl: true,
            // Simple 1:1 coordinates to meters, don't use Mercator or anything like that
            crs: L.CRS.Simple,
            // Context menu when right clicking stuff
            contextmenu: true
        });

        // Refresh the map; draw the layers on it
        this.refreshLeafletMap();

        // Playground


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
    }

    /**
     * Factory for creating a new enemy pack
     * @returns {EnemyPack}
     * @private
     */
    _createEnemyPack(layer){
        switch(this.enemyPackClassName){
            case "AdminEnemyPack":
                return new AdminEnemyPack(this, layer);
            default:
                return new EnemyPack(this, layer);
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
     * Refreshes the leaflet map so
     */
    refreshLeafletMap() {
        if (this.mapTileLayer !== null) {
            this.leafletMap.removeLayer(this.mapTileLayer);
        }
        this.leafletMap.setView([-128, 128], 0);

        this.mapTileLayer = L.tileLayer('https://mpplnr.wofje.nl/images/tiles/' + this.getCurrentDungeon().key + '/' + this.getCurrentFloor().index + '/{z}/{x}_{y}.png', {
            maxZoom: 4,
            attribution: '',
            tileSize: L.point(384, 256),
            noWrap: true,
            continuousWorld: true
        }).addTo(this.leafletMap);

        // Refresh the packs on the map; re-add them
        this.refreshEnemyPacks();
    }

    /**
     * Refreshes the enemy packs that are displayed on the map based on the current dungeon & selected floor.
     */
    refreshEnemyPacks() {
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
                let points = [];
                for (let i = 0; i < json.length; i++) {
                    let floor = json[i];
                    for (let j = 0; j < floor.vertices.length; j++) {
                        let vertex = floor.vertices[j];
                        points.push([vertex.y, vertex.x]);
                    }

                    console.log("points", points);
                    console.log(self.leafletMap);

                    let layer = L.polygon(points, {
                        fillColor: c.map.admin.enemypack.colors.saved,
                        color: c.map.admin.enemypack.colors.savedBorder
                    });

                    self.addEnemyPack(layer);
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
     */
    addEnemyPack(layer) {
        console.log(this.enemyPackClassName);
        let enemyPack = this._createEnemyPack(layer);
        this.enemyPacks.push(enemyPack);

        layer.addTo(this.leafletMap);

        return enemyPack;
    }
}