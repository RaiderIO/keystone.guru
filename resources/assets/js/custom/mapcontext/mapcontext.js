class MapContext extends Signalable {
    constructor(options) {
        super();

        this._options = options;
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
     * @returns {Number}
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
}