class MapContext extends Signalable {
    constructor(options) {
        super();

        this._options = options;

        // Init class colors
        c.map.colorPickerDefaultOptions.swatches = this.getStaticClassColors();

        // Init map icon types
        let mapIconTypes = this._options.static.mapIconTypes;
        this.mapIconTypes = [];
        for (let i = 0; i < mapIconTypes.length; i++) {
            this.mapIconTypes.push(
                new MapIconType(mapIconTypes[i])
            )
        }

        this.unknownMapIconType = this.getMapIconType(this._options.static.unknownMapIconType.id);
        this.awakenedObeliskGatewayMapIconType = this.getMapIconType(this._options.static.awakenedObeliskGatewayMapIconType.id);
    }

    /**
     *
     * @returns {[]}
     */
    getStaticMapIconTypes() {
        return this.mapIconTypes;
    }

    /**
     * Get the Map Icon Type for an ID in the MAP_ICON_TYPES array.
     * @param mapIconTypeId {Number}
     * @returns {MapIconType}
     */
    getMapIconType(mapIconTypeId) {
        let mapIconType = this.getUnknownMapIconType();
        for (let i = 0; i < this.mapIconTypes.length; i++) {
            if (this.mapIconTypes[i].id === mapIconTypeId) {
                mapIconType = this.mapIconTypes[i];
                break;
            }
        }
        return mapIconType;
    }

    /**
     * Gets the default map icon for initializing; when the map icon is unknown.
     * @returns {MapIconType}
     */
    getUnknownMapIconType() {
        return this.unknownMapIconType;
    }

    /**
     * Gets the map icon when clicking the obelisk to place a gateway.
     * @returns {MapIconType}
     */
    getAwakenedObeliskGatewayMapIconType() {
        return this.awakenedObeliskGatewayMapIconType;
    }

    /**
     * Get all the colors of all current classes.
     * @returns {[]}
     */
    getStaticClassColors() {
        return this._options.static.classColors;
    }

    /**
     *
     * @returns {[]}
     */
    getStaticRaidMarkers() {
        return this._options.static.raidMarkers;
    }

    /**
     *
     * @returns {[]}
     */
    getStaticFactions() {
        return this._options.static.factions;
    }

    /**
     *
     * @returns {string}
     */
    getType() {
        return this._options.type;
    }

    /**
     *
     * @returns {string}
     */
    getFaction() {
        return this._options.faction;
    }

    /**
     *
     * @param faction {Number}
     */
    setFaction(faction) {
        this._options.faction = faction;
    }

    /**
     *
     * @returns {Boolean}
     */
    getTeeming() {
        return this._options.teeming;
    }

    /**
     *
     * @param teeming {Boolean}
     */
    setTeeming(teeming) {
        this._options.teeming = teeming;

        // Let everyone know it's changed
        this.signal('teeming:changed', {teeming: this._options.teeming});
    }

    /**
     *
     * @returns {null}
     */
    getFloorId() {
        return this._options.floorId;
    }

    /**
     *
     * @returns {{}}
     */
    getDungeon() {
        return this._options.dungeon;
    }

    /**
     *
     * @returns {[]}
     */
    getEnemies() {
        return this._options.dungeon.enemies;
    }

    /**
     *
     * @returns {[]}
     */
    getEnemyPacks() {
        return this._options.dungeon.enemyPacks;
    }

    /**
     *
     * @returns {[]}
     */
    getEnemyPatrols() {
        return this._options.dungeon.enemyPatrols;
    }

    /**
     *
     * @returns {[]}
     */
    getMapIcons() {
        return this._options.dungeon.mapIcons;
    }

    /**
     *
     * @returns {[]}
     */
    getDungeonFloorSwitchMarkers() {
        return this._options.dungeon.dungeonFloorSwitchMarkers;
    }

    /**
     *
     * @returns {[]}
     */
    getNpcs() {
        return this._options.npcs;
    }

    /**
     * @param npcId {Number}
     * @returns {[]}
     */
    findNpcById(npcId) {
        let result = null;

        for (let i = 0; i < this._options.dungeon.npcs.length; i++) {
            if (this._options.dungeon.npcs[i].id === npcId) {
                result = this._options.dungeon.npcs[i];
                break;
            }
        }

        return result;
    }

    /**
     *
     * @returns {Number}
     */
    getMinEnemySizeDefault() {
        return this._options.minEnemySizeDefault;
    }
    
    /**
     *
     * @returns {Number}
     */
    getMaxEnemySizeDefault() {
        return this._options.maxEnemySizeDefault;
    }
}