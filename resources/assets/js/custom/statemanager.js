class StateManager extends Signalable {
    constructor() {
        super();

        this._debug = false;

        // Any dungeon route we may be editing at this time
        this._mapContext = null;

        // Echo handler
        this.echoEnabled = false;
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
        this.patreonBenefits = [];

        this.snackbarIds = [];
        this.snackbarsAdded = 0;
    }

    /**
     * Enables the Laravel Echo for this session.
     */
    enableEcho() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this.echoEnabled = true;
    }

    /**
     * Sets the mapContext, may either be options for a dungeonroute or options for a dungeon (admin pages)
     * @param {Object} mapContext
     */
    setMapContext(mapContext) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        console.assert(mapContext instanceof Object, 'mapContext is not an Object', mapContext);

        if (mapContext.type === MAP_CONTEXT_TYPE_DUNGEON_ROUTE) {
            this._mapContext = new MapContextDungeonRoute(mapContext);
        } else if (mapContext.type === MAP_CONTEXT_TYPE_LIVE_SESSION) {
            this._mapContext = new MapContextLiveSession(mapContext);
        } else if (mapContext.type === MAP_CONTEXT_TYPE_MAPPING_VERSION_EDIT) {
            this._mapContext = new MapContextMappingVersionEdit(mapContext);
        } else if (mapContext.type === MAP_CONTEXT_TYPE_DUNGEON_EXPLORE) {
            this._mapContext = new MapContextDungeonExplore(mapContext);
        } else {
            console.error(`Unable to find map context type '${mapContext.type}'`);
        }
    }

    setDebug(debug) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._debug = debug;
    }

    isDebug() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this._debug;
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
    setMdtMappingModeEnabled(enabled) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._mdtMappingModeEnabled = enabled;
        this.signal('mdtmappingmodeenabled:changed', {mdtmappingmodeenabled: this._mdtMappingModeEnabled});
    }

    /**
     * Sets the map icon types to be used in the state.
     * @param {Number} mapIconTypes
     */
    setMapIconTypes(mapIconTypes) {
        this.mapIconTypes = [];
    }

    /**
     *
     * @param {Object} patreonBenefits
     */
    setPatreonBenefits(patreonBenefits) {
        this.patreonBenefits = patreonBenefits;
    }

    /**
     * Sets the dungeon map for the state manager.
     * @param {DungeonMap} map
     */
    setDungeonMap(map) {
        let self = this;

        // Unreg ourselves if necessary
        if (this._map !== null) {
            this._map.unregister('map:mapobjectgroupsloaded', this);
        }

        this._map = map;

        this.setEnemyDisplayType(this._map.options.defaultEnemyVisualType);
        this.setUnkilledEnemyOpacity(this._map.options.defaultUnkilledEnemyOpacity);
        this.setUnkilledImportantEnemyOpacity(this._map.options.defaultUnkilledImportantEnemyOpacity);
        this.setEnemyAggressivenessBorder(this._map.options.defaultEnemyAggressivenessBorder);
        this.setMapFacadeStyle(this._map.options.mapFacadeStyle);
        this.setFloorId(this.getMapContext().getInitialFloorId());

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

        // Set up the echo handler if we should
        if (this.isEchoEnabled()) {
            this._echo = new Echo(this._map);
            this._echo.connect();

            this.signal('echo:enabled');
        }
    }

    /**
     * Sets the visual type that is currently being displayed.
     * @param {Number} enemyDisplayType
     */
    setEnemyDisplayType(enemyDisplayType) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        this._enemyDisplayType = enemyDisplayType;

        Cookies.set('enemy_display_type', this._enemyDisplayType, cookieDefaultAttributes);

        // Let everyone know it's changed
        this.signal('enemydisplaytype:changed', {enemyDisplayType: this._enemyDisplayType});
    }

    /**
     * Sets the opacity at which unkilled enemies should be rendered.
     * @param {Number} unkilledEnemyOpacity
     */
    setUnkilledEnemyOpacity(unkilledEnemyOpacity) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        Cookies.set('map_unkilled_enemy_opacity', unkilledEnemyOpacity, cookieDefaultAttributes);

        // Let everyone know it's changed
        this.signal('unkilledenemyopacity:changed', {opacity: unkilledEnemyOpacity});
    }

    /**
     * Sets the opacity at which unkilled important enemies should be rendered.
     * @param {Number} unkilledImportantEnemyOpacity
     */
    setUnkilledImportantEnemyOpacity(unkilledImportantEnemyOpacity) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        Cookies.set('map_unkilled_important_enemy_opacity', unkilledImportantEnemyOpacity, cookieDefaultAttributes);

        // Let everyone know it's changed
        this.signal('unkilledimportantenemyopacity:changed', {opacity: unkilledImportantEnemyOpacity});
    }

    /**
     * Sets whether enemies should feature an aggressiveness border or not.
     * @param {Boolean} visible
     */
    setEnemyAggressivenessBorder(visible) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        Cookies.set('map_enemy_aggressiveness_border', visible ? 1 : 0, cookieDefaultAttributes);

        // Let everyone know it's changed
        this.signal('enemyaggressivenessborder:changed', {visible: visible});
    }

    /**
     * Sets whether enemies should feature a dangerous border or not.
     * @param {Boolean} visible
     */
    setEnemyDangerousBorder(visible) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        Cookies.set('map_enemy_dangerous_border', visible ? 1 : 0, cookieDefaultAttributes);

        // Let everyone know it's changed
        this.signal('enemydangerousborder:changed', {visible: visible});
    }

    /**
     * Sets the floor ID.
     * @param {Number} floorId
     * @param {Array} center
     * @param {Number} zoom
     */
    setFloorId(floorId, center = null, zoom = null) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        this._floorId = floorId;

        // Let everyone know it's changed
        this.signal('floorid:changed', {floorId: this._floorId, center: center, zoom: zoom});
    }

    /**
     * Sets the current map zoom level.
     * @param {Number} zoom
     */
    setMapZoomLevel(zoom) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        // Only when actually changed..
        if (zoom !== this._mapZoomLevel) {
            let previousZoomLevel = this._mapZoomLevel;
            this._mapZoomLevel = zoom;

            // Let everyone know it's changed
            this.signal('mapzoomlevel:changed', {
                mapZoomLevel: this._mapZoomLevel,
                previousMapZoomLevel: previousZoomLevel
            });
        }
    }

    /**
     * Sets the data of the currently logged in user.
     * @param {Object|null} userData
     */
    setUserData(userData) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        this._userData = userData;
    }

    /**
     *
     * @param {string} mapFacadeStyle
     */
    setMapFacadeStyle(mapFacadeStyle) {
        Cookies.set('map_facade_style', mapFacadeStyle, cookieDefaultAttributes);

        this.signal('mapfacadestyle:changed');
    }

    /**
     *
     * @param {string} numberStyle
     */
    setMapNumberStyle(numberStyle) {
        Cookies.set('map_number_style', numberStyle, cookieDefaultAttributes);

        this.signal('mapnumberstyle:changed');
    }

    /**
     *
     * @param {string} numberStyle
     */
    setKillZonesNumberStyle(numberStyle) {
        Cookies.set('kill_zones_number_style', numberStyle, cookieDefaultAttributes);

        this.signal('killzonesnumberstyle:changed');
    }

    /**
     * Sets whether to show floor switches in the pull sidebar
     * @param {boolean} visible
     */
    setPullsSidebarFloorSwitchVisibility(visible) {
        Cookies.set('pulls_sidebar_floor_switch_visibility', visible ? 1 : 0, cookieDefaultAttributes);

        this.signal('pullssidebarfloorswitchvisibility:changed');
    }

    /**
     * Sets whether to show all required enemies when viewing a speedrun
     * @param {boolean} visible
     */
    setDungeonSpeedrunRequiredNpcsShowAllEnabled(visible) {
        Cookies.set('dungeon_speedrun_required_npcs_show_all', visible ? 1 : 0, cookieDefaultAttributes);

        this.signal('dungeonspeedrunrequirednpcsshowall:changed');
    }

    /**
     *
     * @param {boolean} enabled
     */
    setEchoCursorsEnabled(enabled) {
        Cookies.set('echo_cursors_enabled', enabled ? 1 : 0, cookieDefaultAttributes);

        this.signal('echocursorsenabled:changed');
    }

    /**
     * Checks if Echo is enabled for the current session.
     * @returns {boolean}
     */
    isEchoEnabled() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this.echoEnabled;
    }

    /**
     * Gets the Echo instance used for Echo communication.
     * @returns {Echo}
     */
    getEcho() {
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
     * @returns {MapContextMappingVersionEdit|MapContextDungeonRoute|MapContextLiveSession|MapContextDungeonExplore}
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

        let pullGradient = this.getMapContext().getPullGradient();
        if (typeof pullGradient !== 'undefined' && pullGradient.length > 0) {
            let handlers = pullGradient.split(',');
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
     * Get the opacity at which unkilled enemies should be rendered at.
     * @returns {string}
     */
    getUnkilledEnemyOpacity() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return Cookies.get('map_unkilled_enemy_opacity');
    }

    /**
     * Get the opacity at which unkilled important enemies should be rendered at.
     * @returns {string}
     */
    getUnkilledImportantEnemyOpacity() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return Cookies.get('map_unkilled_important_enemy_opacity');
    }

    /**
     * Get whether enemies should feature an aggressiveness border or not.
     * @returns {boolean}
     */
    hasEnemyAggressivenessBorder() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return parseInt(Cookies.get('map_enemy_aggressiveness_border')) === 1;
    }

    /**
     * Get whether enemies should feature a dangerous border or not.
     * @returns {boolean}
     */
    hasEnemyDangerousBorder() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return parseInt(Cookies.get('map_enemy_dangerous_border')) === 1;
    }

    /**
     * Get a list of all map icon types.
     * @returns {[]|*[]}
     */
    getMapIconTypes() {
        return this.mapIconTypes;
    }

    /**
     * Checks if the patreon benefit is enabled for the user or not.
     * @returns {boolean}
     */
    hasPatreonBenefit(patreonBenefit) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        let result = false;
        for (let i = 0; i < this.patreonBenefits.length; i++) {
            if (this.patreonBenefits[i] === patreonBenefit) {
                result = true;
                break;
            }
        }
        return result;
    }

    /**
     * Updates the killzones in the local list to the list that was edited by the user.
     * @param{KillZone[]} killZones
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
     * @returns {Number}
     */
    getMapZoomLevel() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this._mapZoomLevel;
    }

    /**
     * Gets the data of the currently selected floor
     * @returns {boolean|{id: Number}}
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
     * Returns true if the map is currently in admin mode, false if not.
     * @returns {boolean}
     */
    isMapAdmin() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this._mapContext instanceof MapContextMappingVersionEdit;
    }

    /**
     * Gets the currently logged-in user's name, or null if not logged in.
     * @returns {*|null}
     */
    getUser() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this._userData;
    }

    /**
     * Checks if the current dungeon has facade enabled AND if we want to see it. False otherwise.
     * @returns {number|boolean}
     */
    isCurrentDungeonFacadeEnabled() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        return this._mapContext.getMappingVersion().facade_enabled &&
            this.getMapFacadeStyle() === MAP_FACADE_STYLE_FACADE;
    }

    /**
     @returns {String}
     */
    getMapFacadeStyle() {
        return Cookies.get('map_facade_style') ?? MAP_FACADE_STYLE_FACADE;
    }

    /**
     * @returns {String}
     */
    getMapNumberStyle() {
        return Cookies.get('map_number_style') ?? NUMBER_STYLE_PERCENTAGE;
    }

    /**
     * @returns {String}
     */
    getKillZonesNumberStyle() {
        return Cookies.get('kill_zones_number_style') ?? NUMBER_STYLE_PERCENTAGE;
    }

    /**
     * @returns {Boolean}
     */
    getPullsSidebarFloorSwitchVisibility() {
        return parseInt(Cookies.get('pulls_sidebar_floor_switch_visibility')) === 1;
    }

    /**
     *
     * @returns {boolean}
     */
    getEchoCursorsEnabled() {
        return parseInt(Cookies.get('echo_cursors_enabled')) === 1;
    }

    /**
     *
     * @returns {boolean}
     */
    getDungeonSpeedrunRequiredNpcsShowAllEnabled() {
        return parseInt(Cookies.get('dungeon_speedrun_required_npcs_show_all')) === 1;
    }

    /**
     * Adds a snackbar to be displayed on the page (only works in map view!)
     *
     * @param {String} html
     * @param {Object} options
     * @return {String} The created Snackbar's id.
     */
    addSnackbar(html, options = {}) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        // Increment and assign
        let snackbarId = `snackbar-${++this.snackbarsAdded}`;
        this.snackbarIds.push(snackbarId);

        this.signal('snackbar:add', {
            id: snackbarId,
            html: html,
            compact: options.hasOwnProperty('compact') ? options.compact : false,
            onDomAdded: options.hasOwnProperty('onDomAdded') ? (typeof options.onDomAdded === 'function' ? options.onDomAdded : null) : null
        });

        return snackbarId;
    }

    /**
     * Removes a snackbar by its id
     * @param {String} snackbarId
     */
    removeSnackbar(snackbarId) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        // Only if it exists
        if (_.indexOf(this.snackbarIds, snackbarId) !== -1) {
            this.signal('snackbar:remove', {
                id: snackbarId
            });

            this.snackbarIds = _.without(this.snackbarIds, snackbarId);
        }
    }

    /**
     *
     * @param {Number} category
     * @param {String} tag
     * @param {Number} value
     */
    sendMetric(category, tag, value = 1) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        $.ajax({
            type: 'POST',
            url: `/ajax/metric`,
            dataType: 'json',
            data: {
                category: category,
                tag: tag,
                value: value
            }
        });
    }

    /**
     *
     * @param {Number} category
     * @param {String} tag
     * @param {Number} value
     */
    sendMetricForDungeonRoute(category, tag, value = 1) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        $.ajax({
            type: 'POST',
            url: `/ajax/metric/route/${this._mapContext.getPublicKey()}`,
            dataType: 'json',
            data: {
                category: category,
                tag: tag,
                value: value
            }
        });
    }
}
