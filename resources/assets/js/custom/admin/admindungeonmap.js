class AdminDungeonMap extends DungeonMap {

    constructor(mapid, dungeonData, options) {
        super(mapid, dungeonData, options);
        this.currentMDTEnemyMappingEnemy = null;
    }

    /**
     * Create instances of all controls that will be added to the map (UI on the map itself)
     * @param editableLayers
     * @returns {*[]}
     * @private
     */
    _getMapControls(editableLayers) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);
        let result = [];

        if (getState().getMapContext().getDungeon().key === DUNGEON_THE_NEXUS) {
            result.push(new FactionDisplayControls(this));
        }

        if (!this.options.readonly) {
            result.push(new AdminDrawControls(this, editableLayers));
        }
        // result.push(new EnemyVisualControls(this));
        result.push(new AdminPanelControls(this));

        if (getState().isEchoEnabled()) {
            result.push(new EchoControls(this));
        }

        return result;
    }

    refreshLeafletMap() {
        super.refreshLeafletMap();

        this.enemyAttaching = new EnemyAttaching(this);
    }

    /**
     * There's no sandbox mode for admins, they just know.
     * @returns {boolean}
     */
    isSandboxModeEnabled() {
        return false;
    }
}
