class StateManager extends Signalable {
    constructor() {
        super();

        // Used by Echo to join the correct channels
        this._appType = '';
        // Any dungeon route we may be editing at this time
        this._mapContext = null;

        // Echo handler
        this._echo = null;
        /** @type {DungeonMap} The DungeonMap instance */
        this._map = null;
        // What enemy visual type we're displaying
        this._enemyDisplayType = null;
        // The currently displayed floor ID
        this._floorId = null;
        // Map zoom level (default = 2)
        this._mapZoomLevel = 2;
        // The enemy that is focused by the user (mouse overed)
        this._focusedEnemy = null;
        // Details about the currently logged in user
        this._userData = null;
        // Whether we're currently in MDT select mode or not
        this._mdtMappingModeEnabled = false;

        // List of static arrays
        this.mapIconTypes = [];
        this.classColors = [];
        this.enemies = [];
        this.factions = [];
        this.raidMarkers = [];
        this.paidTiers = [];

        // Bit of a hack? But for now best solution
        this.unknownMapIconId = 1;
        this.awakenedObeliskGatewayMapIconId = 11;
        // The map icon as found using above ID once the list of map icons is known
        this.unknownMapIcon = null;
        this.awakenedObeliskGatewayMapIcon = null;
    }

    /**
     * Enables the Laravel Echo for this session.
     */
    enableEcho() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        this._echo = new Echo(this);
        this._echo.connect();

        this.signal('echo:enabled');
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
     * Sets the mapContext, may either be options for a dungeonroute or options for a dungeon (admin pages)
     * @param mapContext {Object}
     */
    setMapContext(mapContext) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        console.assert(mapContext instanceof Object, 'mapContext is not an Object', mapContext);

        if (mapContext.type === 'dungeonroute') {
            this._mapContext = new MapContextDungeonRoute(mapContext);
        } else if (mapContext.type === 'dungeon') {
            this._mapContext = new MapContextDungeon(mapContext);
        } else {
            console.error(`Unable to find map context type '${mapContext.type}'`);
        }
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
     * Sets the MDT mapping mode to be enabled or not.
     * @param enabled {boolean}
     */
    setMdtMappingModeEnabled(enabled ){
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._mdtMappingModeEnabled = enabled;
        this.signal('mdtmappingmodeenabled:changed', {mdtmappingmodeenabled: this._mdtMappingModeEnabled});
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
            this._map.unregister('map:mapobjectgroupsloaded', this);
        }

        this._map = map;

        this.setEnemyDisplayType(this._map.options.defaultEnemyVisualType);
        this.setFloorId(this.getMapContext().getFloorId());

        // Change defaults based on the hash if necessary
        if (window.location.hash.length > 0) {
            this._map.register('map:mapobjectgroupsloaded', this, function () {
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
     * Checks if Echo is enabled for the current session.
     * @returns {boolean}
     */
    isEchoEnabled() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this._echo !== null;
    }

    /**
     * Gets the Echo instance used for Echo communication.
     * @returns {Echo}
     */
    getEcho(){
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this._echo;
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
     * Get the context of the map we are editing at this point.
     * @returns {MapContextDungeon|MapContextDungeonRoute}
     */
    getMapContext() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this._mapContext;
    }

    /**
     * Gets if the MDT mapping mode is currently enabled or not.
     * @returns {boolean}
     */
    getMdtMappingModeEnabled() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this._mdtMappingModeEnabled;
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
        $.each(this._mapContext.getDungeon().floors, function (index, value) {
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

        return this._mapContext.getType() === 'dungeon';
    }

    /**
     *
     * @returns {*}
     */
    getEchoChannelName() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        let channelName = '';

        if (this.isMapAdmin()) {
            channelName = `${this._appType}-dungeon-edit.${this._mapContext.getDungeon().id}`;
        } else {
            channelName = `${this._appType}-route-edit.${this._mapContext.getPublicKey()}`;
        }

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