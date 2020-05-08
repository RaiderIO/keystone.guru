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

        if (this.dungeonData.name === 'Siege of Boralus') {
            result.push(new FactionDisplayControls(this));
        }

        result.push(new AdminDrawControls(this, editableLayers));
        result.push(new EnemyVisualControls(this));
        result.push(new MapObjectGroupControls(this));
        result.push(new AdminPanelControls(this));

        return result;
    }

    /**
     *
     * @returns {[]}
     * @protected
     */
    _createMapObjectGroups() {
        // For this page, let the enemy pack be the admin version with more functions which are otherwise hidden from the user
        return [
            new EnemyMapObjectGroup(this, 'enemy', 'AdminEnemy', true),
            new EnemyPatrolMapObjectGroup(this, 'enemypatrol', 'AdminEnemyPatrol', true),
            new EnemyPackMapObjectGroup(this, 'enemypack', 'AdminEnemyPack', true),
            new DungeonStartMarkerMapObjectGroup(this, 'dungeonstartmarker', 'AdminDungeonStartMarker', true),
            new DungeonFloorSwitchMarkerMapObjectGroup(this, 'dungeonfloorswitchmarker', 'AdminDungeonFloorSwitchMarker', true),
            new MapIconMapObjectGroup(this, 'mapicon', 'AdminMapComment', true),
        ];
    }

    refreshLeafletMap() {
        super.refreshLeafletMap();

        this.enemyAttaching = new EnemyAttaching(this);
    }

    /**
     * There's no try mode for admins, they just know.
     * @returns {boolean}
     */
    isTryModeEnabled() {
        return false;
    }
}