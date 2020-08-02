class StateManager extends Signalable {
    constructor() {
        super();

        // Used by Echo to join the correct channels
        this._appType = '';
        // Any dungeon route we may be editing at this time
        this.dungeonRoute = null;
        // The data of the dungeon that we're editing
        this.dungeonData = null;

        this._map = null;
        // What enemy visual type we're displaying
        this._enemyDisplayType = null;
        // The currently displayed floor ID
        this._floorId = null;
        // Map zoom level (default = 2)
        this._mapZoomLevel = 2;
        // Seasonal index (shows certain enemies or not)
        this._seasonalIndex = 0;
        // Teeming or not (shows certain enemies or not)
        this._teeming = false;
        // Pull gradient variables
        this._pullGradient = '';
        this._pullGradientApplyAlways = false;
        // The enemy that is focused by the user (mouse overed)
        this._focusedEnemy = null;
        // Details about the currently logged in user
        this._userData = null;

        // List of static arrays
        this.mapIconTypes = [];
        this.classColors = [];
        this.enemies = [];
        this.rawEnemies = [];
        this.mdtEnemies = [];
        this.factions = [];
        this.raidMarkers = [];
        this.killZones = [];
        this.paidTiers = [];

        // Bit of a hack? But for now best solution
        this.unknownMapIconId = 1;
        this.awakenedObeliskGatewayMapIconId = 11;
        // The map icon as found using above ID once the list of map icons is known
        this.unknownMapIcon = null;
        this.awakenedObeliskGatewayMapIcon = null;
    }

    /**
     * Set the app type (local, staging, live etc).
     * @param appType {string}
     */
    setAppType(appType) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._appType = appType;
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
        this.setTeeming(this.dungeonRoute.teeming);
        this.setPullGradient(this.dungeonRoute.pullGradient);
        this.setPullGradientApplyAlways(this.dungeonRoute.pullGradientApplyAlways);
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
     * @param seasonalIndex {int}
     */
    setSeasonalIndex(seasonalIndex) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._seasonalIndex = seasonalIndex;

        // Let everyone know it's changed
        this.signal('seasonalindex:changed', {seasonalIndex: this._seasonalIndex});
    }

    /**
     * Sets the Teeming state of the map.
     * @param teeming {boolean}
     */
    setTeeming(teeming) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._teeming = teeming;

        // Let everyone know it's changed
        this.signal('teeming:changed', {teeming: this._teeming});
    }

    /**
     * Sets the pull gradient of the current dungeon route.
     * @param value {string}
     */
    setPullGradient(value) {
        this._pullGradient = value;

        // Let everyone know it's changed
        this.signal('pullgradient:changed', {pullgradient: this._pullGradient});
    }

    /**
     * Sets the pull gradient to always apply when a change is made to any pulls.
     * @param value {boolean}
     */
    setPullGradientApplyAlways(value) {
        this._pullGradientApplyAlways = value;

        // Let everyone know it's changed
        this.signal('pullgradientapplyalways:changed', {pullgradientapplyalways: this._pullGradientApplyAlways});
    }

    /**
     * Gets the currently focused enemy.
     * @param enemy {Enemy}
     */
    setFocusedEnemy(enemy) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._focusedEnemy = enemy;
        this.signal('focusedenemy:changed', {focusedenemy: this._focusedEnemy});
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
        this.awakenedObeliskGatewayMapIcon = this.getMapIconType(this.awakenedObeliskGatewayMapIconId);

        // Defined in mapicon.js, need to fix this somehow
        initAwakenedObeliskGatewayIcon();
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
     *
     * @param killZones
     */
    setKillZones(killZones) {
        this.killZones = killZones;
    }

    /**
     *
     * @param paidTiers
     */
    setPaidTiers(paidTiers) {
        this.paidTiers = paidTiers;
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
        this._enemyDisplayType = enemyDisplayType;

        Cookies.set('enemy_display_type', this._enemyDisplayType);

        // Let everyone know it's changed
        this.signal('enemydisplaytype:changed', {enemyDisplayType: this._enemyDisplayType});
    }

    /**
     * Sets the floor ID.
     * @param floorId int
     */
    setFloorId(floorId) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        this._floorId = floorId;

        // Let everyone know it's changed
        this.signal('floorid:changed', {floorId: this._floorId});
    }

    /**
     * Sets the current map zoom level.
     * @param zoom
     */
    setMapZoomLevel(zoom) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        // Only when actually changed..
        if (zoom !== this._mapZoomLevel) {
            this._mapZoomLevel = zoom;

            // Let everyone know it's changed
            this.signal('mapzoomlevel:changed', {mapZoomLevel: this._mapZoomLevel});
        }
    }

    /**
     * Sets the data of the currently logged in user.
     * @param userData {Object|null}
     */
    setUserData(userData) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._userData = userData;
    }

    /**
     * Gets the dungeon map if it's set before.
     * @returns {DungeonMap}
     */
    getDungeonMap() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
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
        return this._seasonalIndex;
    }

    /**
     * Gets the current teeming state of the map.
     * @returns {number}
     */
    getTeeming() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this._teeming;
    }

    /**
     * Get the pull gradient for the current dungeon route.
     * @returns {string}
     */
    getPullGradient() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this._pullGradient;
    }

    /**
     * Fetches a handler structure from a cookie
     * @returns {[]}
     * @private
     */
    getPullGradientHandlers() {
        let result = [];

        if (typeof this._pullGradient !== 'undefined' && this._pullGradient.length > 0) {
            let handlers = this._pullGradient.split(',');
            for (let index in handlers) {
                if (handlers.hasOwnProperty(index)) {
                    let handler = handlers[index];
                    let values = handler.trim().split(' ');
                    // Only RGB values
                    if (values[1].indexOf('#') === 0) {
                        result.push([parseInt(('' + values[0]).replace('%', '')), values[1]]);
                    } else {
                        console.warn('Invalid handler found:', handler);
                    }
                }
            }
        } else {
            result = c.map.editsidebar.pullGradient.defaultHandlers;
        }

        return result;
    }

    /**
     * Gets if the current pull gradient should always be applied when new pulls are added/re-ordered.
     * @returns {boolean}
     */
    getPullGradientApplyAlways() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this._pullGradientApplyAlways;
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
        return this._enemyDisplayType;
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
     * Get all killzones
     * @returns {[]}
     */
    getKillZones() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.killZones;
    }

    /**
     * Checks if the paid tier is enabled for the user or not.
     * @returns {boolean}
     */
    hasPaidTier(paidTier) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        let result = false;
        for (let i = 0; i < this.paidTiers.length; i++) {
            if (this.paidTiers[i] === paidTier) {
                result = true;
                break;
            }
        }
        return result;
    }

    /**
     * Updates the killzones in the local list to the list that was edited by the user.
     * @param killZones {KillZone[]}
     */
    updateKillZones(killZones) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        let result = [];

        for (let index in killZones) {
            let killZone = killZones[index];

            let killzonenemies = [];
            for (let enemyIndex in killZone.enemies) {
                if (killZone.enemies.hasOwnProperty(enemyIndex)) {
                    killzonenemies.push({
                        enemy_id: killZone.enemies[enemyIndex]
                    });
                }
            }

            result.push({
                id: killZone.id,
                floor_id: killZone.floor_id,
                color: killZone.color,
                killzonenemies: killzonenemies,
                lat: killZone.layer !== null ? killZone.layer.getLatLng().lat : null,
                lng: killZone.layer !== null ? killZone.layer.getLatLng().lng : null,
            })
        }

        this.killZones = result;
    }

    /**
     * Get the current map's zoom level.
     * @returns {int}
     */
    getMapZoomLevel() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this._mapZoomLevel;
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
            if (parseInt(value.id) === parseInt(self._floorId)) {
                result = value;
                return false;
            }
        });

        return result;
    }

    /**
     * Gets the default map icon for initializing; when the map icon is unknown.
     * @returns {MapIconType}
     */
    getUnknownMapIconType() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this.unknownMapIcon;
    }

    /**
     * Gets the map icon when clicking the obelisk to place a gateway.
     * @returns {number}
     */
    getAwakenedObeliskGatewayMapIconType() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this.awakenedObeliskGatewayMapIcon;
    }

    /**
     * Returns true if the map is currently in admin mode, false if not.
     * @returns {boolean}
     */
    isMapAdmin() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this.dungeonRoute.publicKey === 'admin';
    }

    /**
     *
     * @returns {*}
     */
    getEchoChannelName() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        let channelName = '';

        if (this.isMapAdmin()) {
            channelName = `${this._appType}-dungeon-edit.${this.dungeonData.id}`;
        } else {
            channelName = `${this._appType}-route-edit.${this.dungeonRoute.publicKey}`;
        }

        console.log(channelName);

        return channelName;
    }

    /**
     * Gets the currently logged in user's name, or null if not logged in.
     * @returns {*|null}
     */
    getUserName() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this._userData !== null ? this._userData.name : null;
    }
}