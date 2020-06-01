class StateManager extends Signalable {
    constructor() {
        super();

        // Any dungeon route we may be editing at this time
        this.dungeonRoute = null;
        // The data of the dungeon that we're editing
        this.dungeonData = null;

        this._map = null;
        // What enemy visual type we're displaying
        this.enemyDisplayType = null;
        // The currently displayed floor ID
        this.floorId = null;
        // Map zoom level (default = 2)
        this.mapZoomLevel = 2;
        // Seasonal index (shows certain enemies or not
        this.seasonalIndex = 0;

        // List of static arrays
        this.mapIconTypes = [];
        this.classColors = [];
        this.enemies = [];
        this.rawEnemies = [];
        this.mdtEnemies = [];
        this.factions = [];
        this.raidMarkers = [];

        // Bit of a hack? But for now best solution
        this.unknownMapIconId = 1;
        // The map icon as found using above ID once the list of map icons is known
        this.unknownMapIcon = null;
    }

    /**
     * Sets the dungeon route that we're currently editing (may be null)
     * @param dungeonRoute
     */
    setDungeonRoute(dungeonRoute) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        console.assert(dungeonRoute instanceof Object, 'dungeonRoute is not an Object', dungeonRoute);

        this.dungeonRoute = dungeonRoute;
        // Load this from the start here
        this.setSeasonalIndex(parseInt(this.dungeonRoute.seasonalIndex));
    }

    /**
     * Sets the data that describes the current dungeon.
     * @param dungeonData
     */
    setDungeonData(dungeonData) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        console.assert(dungeonData instanceof Object, 'dungeonData is not an Object', dungeonData);

        this.dungeonData = dungeonData;
    }

    /**
     *
     * @param seasonalIndex
     */
    setSeasonalIndex(seasonalIndex) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this.seasonalIndex = seasonalIndex;

        // Let everyone know it's changed
        this.signal('seasonalindex:changed', {seasonalIndex: this.seasonalIndex});
    }

    /**
     * Sets the map icon types to be used in the state.
     * @param mapIconTypes int
     */
    setMapIconTypes(mapIconTypes) {
        this.mapIconTypes = [];
        for (let i = 0; i < mapIconTypes.length; i++) {
            this.mapIconTypes.push(
                new MapIconType(mapIconTypes[i])
            )
        }
        this.unknownMapIcon = this.getMapIconType(this.unknownMapIconId);
    }

    /**
     * Sets the class colors.
     * @param classColors
     */
    setClassColors(classColors) {
        this.classColors = classColors;

        c.map.colorPickerDefaultOptions.swatches = this.classColors;
    }

    /**
     * Sets the enemies.
     * @param enemies
     */
    setEnemies(enemies) {
        this.enemies = enemies;
    }

    /**
     * Sets the raw enemies (not converted to Enemy classes yet; pure objects)
     * @param rawEnemies
     */
    setRawEnemies(rawEnemies) {
        this.rawEnemies = rawEnemies;
    }

    /**
     * Sets the MDT enemies.
     * @param mdtEnemies
     */
    setMdtEnemies(mdtEnemies) {
        this.mdtEnemies = mdtEnemies;
    }

    /**
     * Sets the raid markers.
     * @param raidMarkers
     */
    setRaidMarkers(raidMarkers) {
        this.raidMarkers = raidMarkers;
    }

    /**
     *
     * @param factions
     */
    setFactions(factions) {
        this.factions = factions;
    }

    /**
     * Sets the dungeon map for the state manager.
     * @param map DungeonMap
     */
    setDungeonMap(map) {
        let self = this;

        // Unreg ourselves if necessary
        if (this._map !== null) {
            this._map.unregister('map:mapobjectgroupsfetchsuccess', this);
        }

        this._map = map;

        this.setEnemyDisplayType(this._map.options.defaultEnemyVisualType);
        this.setFloorId(this._map.options.floorId);

        // Change defaults based on the hash if necessary
        if (window.location.hash.length > 0) {
            this._map.register('map:mapobjectgroupsfetchsuccess', this, function () {
                // Fill the hashVariables with key=>value pairs
                let hashVariables = {};
                let variables = window.location.hash.replace('#', '').split('&');

                for (let i = 0; i < variables.length; i++) {
                    let variable = variables[i];

                    let keyValue = variable.split('=');
                    hashVariables[keyValue[0]] = keyValue[1];
                }

                // Enemy display type
                if (hashVariables.hasOwnProperty('display')) {
                    self.setEnemyDisplayType(hashVariables.display);
                }
            });
        }
    }

    /**
     * Sets the visual type that is currently being displayed.
     * @param enemyDisplayType int
     */
    setEnemyDisplayType(enemyDisplayType) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        this.enemyDisplayType = enemyDisplayType;

        Cookies.set('enemy_display_type', this.enemyDisplayType);

        // Let everyone know it's changed
        this.signal('enemydisplaytype:changed', {enemyDisplayType: this.enemyDisplayType});
    }

    /**
     * Sets the floor ID.
     * @param floorId int
     */
    setFloorId(floorId) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        this.floorId = floorId;

        // Let everyone know it's changed
        this.signal('floorid:changed', {floorId: this.floorId});
    }

    /**
     * Sets the current map zoom level.
     * @param zoom
     */
    setMapZoomLevel(zoom) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        // Only when actually changed..
        if (zoom !== this.mapZoomLevel) {
            this.mapZoomLevel = zoom;

            // Let everyone know it's changed
            this.signal('mapzoomlevel:changed', {mapZoomLevel: this.mapZoomLevel});
        }
    }

    /**
     * Gets the dungeon map if it's set before.
     * @returns {null}
     */
    getDungeonMap() {
        return this._map;
    }

    /**
     * Get the dungeon route we may or may not be editing at this time.
     * @returns {null}
     */
    getDungeonRoute() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.dungeonRoute;
    }

    /**
     * Gets the current seasonal index.
     * @returns {number}
     */
    getSeasonalIndex() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this.seasonalIndex;
    }

    /**
     * Gets the data of the dungeon that we're currently editing.
     * @returns {null}
     */
    getDungeonData() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.dungeonData;
    }

    /**
     * Get the default visual to display for all enemies.
     * @returns {string}
     */
    getEnemyDisplayType() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.enemyDisplayType;
    }

    /**
     * Get all the colors of all current classes.
     * @returns {[]}
     */
    getClassColors() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.classColors;
    }

    /**
     * Get a list of all map icon types.
     * @returns {[]|*[]}
     */
    getMapIconTypes() {
        return this.mapIconTypes;
    }

    /**
     * Get the Map Icon Type for an ID in the MAP_ICON_TYPES array.
     * @param mapIconTypeId
     * @returns {null}
     */
    getMapIconType(mapIconTypeId) {
        let mapIconType = this.unknownMapIcon;
        for (let i = 0; i < this.mapIconTypes.length; i++) {
            if (this.mapIconTypes[i].id === mapIconTypeId) {
                mapIconType = this.mapIconTypes[i];
                break;
            }
        }
        return mapIconType;
    }

    /**
     * Get all the enemies of this dungeon.
     * @returns {[]}
     */
    getEnemies() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.enemies;
    }

    /**
     * Find an enemy by an ID
     * @param enemyId
     * @returns {null}
     */
    getEnemyById(enemyId) {
        let enemy = null;
        for (let i = 0; i < this.enemies.length; i++) {
            if (this.enemies[i].id === enemyId) {
                enemy = this.enemies[i];
                break;
            }
        }
        return enemy;
    }

    /**
     * Get all the raw enemies of this dungeon.
     * @returns {[]}
     */
    getRawEnemies() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.rawEnemies;
    }

    /**
     * Get all the mdt enemies of this dungeon.
     * @returns {[]}
     */
    getMdtEnemies() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.mdtEnemies;
    }

    /**
     * Get all factions.
     * @returns {[]}
     */
    getFactions() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.factions;
    }

    /**
     * Get all raid markers.
     * @returns {[]}
     */
    getRaidMarkers() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.raidMarkers;
    }

    /**
     * Get the current map's zoom level.
     * @returns {int}
     */
    getMapZoomLevel() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.mapZoomLevel;
    }

    /**
     * Gets the data of the currently selected floor
     * @returns {boolean|Object}
     */
    getCurrentFloor() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        let self = this;
        let result = false;
        // Iterate over the found floors
        $.each(this.dungeonData.floors, function (index, value) {
            // Find the floor we're looking for
            if (parseInt(value.id) === parseInt(self.floorId)) {
                result = value;
                return false;
            }
        });

        return result;
    }

    /**
     * Gets the default map icon for initializing; when the map icon is unknown.
     * @returns {null}
     */
    getUnknownMapIcon() {
        return this.unknownMapIcon;
    }

    /**
     * Returns true if the map is currently in admin mode, false if not.
     * @returns {boolean}
     */
    isMapAdmin() {
        return this.dungeonRoute.publicKey === 'admin';
    }
}